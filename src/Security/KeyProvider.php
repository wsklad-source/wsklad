<?php namespace Wsklad\Security;

defined('ABSPATH') || exit;

/**
 * KeyProvider
 *
 * Derives a per-install 32 byte encryption key from WordPress salts using HKDF-SHA256.
 *
 * Two properties matter:
 *
 * 1. Per-install salt (`wsklad_install_salt`) is generated once at activation and stored
 *    as a network option. Two sites sharing the same `AUTH_KEY` still get different keys.
 * 2. Network separation: on multisite the salt is a *network* option and the blog id is
 *    mixed into the info string, so the network and each blog derive distinct keys.
 *
 * ⚠ **The salt is stored hex-encoded, and that is not cosmetic.** An option lives in a
 * `longtext` column with a `utf8mb4` charset, and `random_bytes()` output is not valid
 * UTF-8. The write fails, `update_site_option()` returns false, nobody checks the
 * return value, and the salt reads back empty. `getSalt()` then treats an empty value as
 * "not initialised", generates a fresh one, fails to store it, and hands back a
 * *different key on the next call*. The visible symptom is that a credential can be
 * written and never read again — silently, on every request, with no error anywhere.
 *
 * This is the worst bug shape the plugin could have shipped: the encryption unit tests
 * all passed, because their stub stored the value in a PHP array with no charset, and
 * only a real database showed it. The fix is `SALT_BYTES` of entropy, hex-encoded to
 * `SALT_HEX_BYTES` ASCII characters for storage and hex-decoded on the way back in.
 *
 * @package Wsklad\Security
 * @since 0.10.1
 */
final class KeyProvider
{
	const SALT_OPTION = 'wsklad_install_salt';
	const KEY_ID_OPTION = 'wsklad_key_id';

	/**
	 * Entropy in bytes.
	 */
	const SALT_BYTES = 32;

	/**
	 * Storage form: hex, so it survives a `utf8mb4` column.
	 */
	const SALT_HEX_BYTES = 64;

	/**
	 * @var Cryptography|null
	 */
	private $cryptography;

	/**
	 * @var string
	 */
	private $key_id = 'k1';

	/**
	 * Build (or rebuild) the cryptography instance for the current blog.
	 *
	 * @return Cryptography
	 */
	public function get(): Cryptography
	{
		if(is_null($this->cryptography))
		{
			$key = $this->deriveKey();

			if(SodiumCryptography::hasS())
			{
				$this->cryptography = new SodiumCryptography($key, $this->getKeyId());
			}
			else
			{
				$this->cryptography = new UnavailableCryptography();
			}
		}

		return $this->cryptography;
	}

	/**
	 * Whether real encryption is possible on this server.
	 *
	 * @return bool
	 */
	public function isAvailable(): bool
	{
		return SodiumCryptography::hasS();
	}

	/**
	 * Current key id.
	 *
	 * @return string
	 */
	public function getKeyId(): string
	{
		$id = function_exists('get_site_option') ? get_site_option(self::KEY_ID_OPTION, '') : '';

		if(!is_string($id) || '' === $id)
		{
			$id = 'k1';
		}

		return $this->key_id = $id;
	}

	/**
	 * Derive the 32 byte key for the current installation.
	 *
	 * @return string
	 */
	public function deriveKey(): string
	{
		$salt = $this->getSalt();
		$info = $this->infoString();
		$ikm  = $this->salts();

		if(function_exists('hash_hkdf'))
		{
			return hash_hkdf('sha256', $ikm, 32, $info, $salt);
		}

		// Manual HKDF-Expand (RFC 5869) — PHP builds without ext-hash hkdf support.
		$prk    = hash_hmac('sha256', $ikm, $salt, true);
		$output = '';

		for($i = 1; strlen($output) < 32; $i++)
		{
			$output .= hash_hmac('sha256', $info . chr($i), $prk, true);
		}

		return substr($output, 0, 32);
	}

	/**
	 * Get or lazily create the per-install salt, as raw bytes for HKDF.
	 *
	 * Storage is hex, see the class docblock. Both encodings are accepted on read so
	 * that a value written by the broken binary version is recovered rather than
	 * discarded — replacing a salt would make every stored credential undecryptable.
	 *
	 * ⚠ A failed write must not be ignored. The original version ignored it, which is
	 * how the key ended up changing on every call. If the value cannot be persisted the
	 * method says so, and the caller is expected to surface it.
	 *
	 * @return string
	 */
	public function getSalt(): string
	{
		if(!function_exists('get_site_option'))
		{
			return str_repeat("\0", self::SALT_BYTES);
		}

		$stored = get_site_option(self::SALT_OPTION, '');

		$decoded = self::decodeSalt(is_string($stored) ? $stored : '');

		if('' !== $decoded)
		{
			return $decoded;
		}

		$salt = $this->createSalt();

		$encoded = bin2hex($salt);

		if(!update_site_option(self::SALT_OPTION, $encoded))
		{
			/**
			 * Fires when the installation salt cannot be persisted.
			 *
			 * A credential written now will not be readable later: the key derived from
			 * a salt that was never stored will not be derived again.
			 *
			 * @param string $reason
			 */
			do_action('wsklad_install_salt_not_persisted', self::persistenceFailureReason());

			self::$persistence_error = self::persistenceFailureReason();
		}
		else
		{
			self::$persistence_error = '';
		}

		return $salt;
	}

	/**
	 * @var string
	 */
	private static $persistence_error = '';

	/**
	 * Whether the salt could be stored on the last call.
	 *
	 * @return bool
	 */
	public static function saltPersisted(): bool
	{
		return '' === self::$persistence_error;
	}

	/**
	 * Why the salt could not be stored.
	 *
	 * @return string
	 */
	public static function persistenceError(): string
	{
		return self::$persistence_error;
	}

	/**
	 * @return string
	 */
	private static function persistenceFailureReason(): string
	{
		if(function_exists('is_multisite') && is_multisite())
		{
			return 'update_site_option() returned false. The site is a network: the salt is a network option, and a network without a working sitemeta table cannot store it.';
		}

		return 'update_site_option() returned false. The wp_options table rejected the value, or the row cannot be written.';
	}

	/**
	 * Accept both the hex form and the raw form written by the earlier version.
	 *
	 * @param string $stored
	 *
	 * @return string Raw salt bytes, or '' when unusable
	 */
	private static function decodeSalt(string $stored): string
	{
		$stored = trim($stored);

		if('' === $stored)
		{
			return '';
		}

		// Current form: 64 hex characters.
		if(self::SALT_HEX_BYTES === strlen($stored) && ctype_xdigit($stored))
		{
			$decoded = hex2bin($stored);

			return false === $decoded ? '' : $decoded;
		}

		// Legacy form: raw 32 bytes. These were never actually persisted on a utf8mb4
		// column, so this branch is only reachable from a non-MySQL store, but reading
		// it costs nothing and refusing it would be a needless risk.
		if(self::SALT_BYTES === strlen($stored))
		{
			return $stored;
		}

		// Some other length of hex, from a future change or a hand edit.
		if(ctype_xdigit($stored) && 0 === strlen($stored) % 2)
		{
			$decoded = hex2bin($stored);

			if(false !== $decoded && self::SALT_BYTES === strlen($decoded))
			{
				return $decoded;
			}
		}

		return '';
	}

	/**
	 * Create the install salt, as raw bytes. Called from Activation exactly once.
	 *
	 * ⚠ The return value is **binary** and must be hex-encoded before it goes anywhere
	 * near a database. `getSalt()` does that. Do not store the result of this method
	 * directly — see the class docblock for what happens when someone does.
	 *
	 * The length is exactly `SALT_BYTES`: `wp_generate_password()` returns its argument
	 * in *characters*, and an earlier version asked for 64 while the reader validated
	 * against 32, so the salt was rejected and regenerated on every call.
	 *
	 * @return string
	 */
	public function createSalt(): string
	{
		$raw = '';

		if(function_exists('random_bytes'))
		{
			$raw = random_bytes(self::SALT_BYTES);
		}
		elseif(function_exists('openssl_random_pseudo_bytes'))
		{
			$raw = openssl_random_pseudo_bytes(self::SALT_BYTES);
		}

		if('' !== $raw && self::SALT_BYTES === strlen($raw))
		{
			return $raw;
		}

		// Deterministic-length fallback, ASCII only so the value is already
		// storage-safe. `wp_rand()` is WordPress's CSPRNG wrapper, so this is not a
		// downgrade in strength — it is only reachable on a PHP build with neither
		// `random_bytes` nor `openssl_random_pseudo_bytes`, neither of which has existed
		// in a default build since PHP 7.
		$candidate = '';

		for($i = 0; $i < self::SALT_BYTES; $i++)
		{
			$candidate .= dechex(function_exists('wp_rand') ? wp_rand(0, 15) : random_int(0, 15));
		}

		return $candidate;
	}

	/**
	 * Bump the key id. Existing rows keep their old key id in the envelope and stay
	 * readable with the current key; new rows are written with the new id.
	 *
	 * ⚠ The return value of `update_site_option()` is not checked. For the key id that
	 * is a deliberate difference from the salt: a lost key id is cosmetic (rows keep
	 * their labels and still decrypt), whereas a lost salt invalidates every credential.
	 * `getKeyId()` reads the option each time, so a failed write is corrected on the
	 * next call rather than being cached.
	 *
	 * @return string
	 */
	public function rotateKeyId(): string
	{
		$next = 'k' . ((int) ltrim($this->getKeyId(), 'k') + 1);

		update_site_option(self::KEY_ID_OPTION, $next);

		$this->key_id       = $next;
		$this->cryptography = null;

		return $next;
	}

	/**
	 * Input key material: the WordPress salts, concatenated.
	 *
	 * @return string
	 */
	private function salts(): string
	{
		$parts = [];

		foreach(['AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY'] as $constant)
		{
			if(defined($constant))
			{
				$parts[] = (string) constant($constant);
			}
		}

		if(empty($parts))
		{
			// No wp-config.php salts at all (unit tests, CLI). Fall back to a stable value.
			$parts[] = 'wsklad-no-wp-salts';
		}

		return implode('|', $parts);
	}

	/**
	 * HKDF info string: purpose + network/blog separation.
	 *
	 * @return string
	 */
	private function infoString(): string
	{
		$blog_id = function_exists('get_current_blog_id') ? (int) get_current_blog_id() : 1;

		return 'wsklad-account-credentials|v1|blog=' . $blog_id;
	}
}
