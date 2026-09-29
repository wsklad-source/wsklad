<?php namespace Wsklad\Security;

defined('ABSPATH') || exit;

/**
 * SodiumCryptography
 *
 * XChaCha20-Poly1305 AEAD, envelope format: `v1$<key_id>$<base64 nonce>$<base64 cipher>`.
 *
 * The key id is stored next to the cipher text so that key rotation can decrypt
 * old rows with the old key while writing new rows with the new one.
 *
 * Requires libsodium (`ext-sodium`) or PHP >= 7.2 with the bundled polyfill.
 *
 * @package Wsklad\Security
 * @since 0.10.0
 */
final class SodiumCryptography implements Cryptography
{
	const PREFIX = 'v1$';

	/**
	 * @var string Binary 32 byte encryption key
	 */
	private $key;

	/**
	 * @var string Short key identifier, safe for the envelope
	 */
	private $key_id;

	/**
	 * @var string Last error message
	 */
	private $error = '';

	/**
	 * @param string $key    Binary 32 byte key
	 * @param string $key_id Key identifier
	 */
	public function __construct(string $key, string $key_id = 'k1')
	{
		$this->key    = $key;
		$this->key_id = $key_id;
	}

	/**
	 * @return bool
	 */
	public function isAvailable(): bool
	{
		return self::hasS();
	}

	/**
	 * @return string
	 */
	public function format(): string
	{
		return self::PREFIX;
	}

	/**
	 * @param string $value
	 *
	 * @return bool
	 */
	public function isEncrypted(string $value): bool
	{
		return 0 === strpos($value, self::PREFIX) && substr_count($value, '$') >= 3;
	}

	/**
	 * @param string $plaintext
	 *
	 * @return string
	 */
	public function encrypt(string $plaintext): string
	{
		$this->error = '';

		if('' === $plaintext)
		{
			return '';
		}

		if(!$this->isAvailable())
		{
			$this->error = 'sodium is not available';

			return '';
		}

		try
		{
			$nonce = self::randomBytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
			$cipher = self::aeadEncrypt($this->key, $nonce, $plaintext, self::PREFIX);

			return self::PREFIX . $this->key_id . '$' . base64_encode($nonce) . '$' . base64_encode($cipher);
		}
		catch(\Throwable $e)
		{
			$this->error = $e->getMessage();

			return '';
		}
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public function decrypt(string $value): string
	{
		$this->error = '';

		if('' === $value)
		{
			return '';
		}

		if(!$this->isEncrypted($value))
		{
			// Legacy plain text written before 0.10.0. Return as is.
			return $value;
		}

		if(!$this->isAvailable())
		{
			$this->error = 'sodium is not available';

			return '';
		}

		$parts = explode('$', $value);

		if(4 !== count($parts))
		{
			$this->error = 'malformed envelope';

			return '';
		}

		$nonce  = base64_decode($parts[2], true);
		$cipher = base64_decode($parts[3], true);

		if(false === $nonce || false === $cipher)
		{
			$this->error = 'malformed base64 payload';

			return '';
		}

		try
		{
			$plain = self::aeadDecrypt($this->key, $nonce, $cipher, self::PREFIX);

			if(false === $plain)
			{
				$this->error = 'authentication failed (wrong key or tampered data)';

				return '';
			}

			return $plain;
		}
		catch(\Throwable $e)
		{
			$this->error = $e->getMessage();

			return '';
		}
	}

	/**
	 * @return string
	 */
	public function getError(): string
	{
		return $this->error;
	}

	/**
	 * libsodium availability.
	 *
	 * @return bool
	 */
	public static function hasS(): bool
	{
		return function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt');
	}

	/**
	 * @param string $key
	 * @param string $nonce
	 * @param string $plaintext
	 * @param string $aad
	 *
	 * @return string
	 */
	private static function aeadEncrypt(string $key, string $nonce, string $plaintext, string $aad): string
	{
		$cipher = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plaintext, $aad, $nonce, $key);

		return null === $cipher ? '' : $cipher;
	}

	/**
	 * @param string $key
	 * @param string $nonce
	 * @param string $cipher
	 * @param string $aad
	 *
	 * @return string|false
	 */
	private static function aeadDecrypt(string $key, string $nonce, string $cipher, string $aad)
	{
		return sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($cipher, $aad, $nonce, $key);
	}

	/**
	 * Cryptographically secure random bytes.
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	private static function randomBytes(int $length): string
	{
		if(function_exists('random_bytes'))
		{
			return random_bytes($length);
		}

		if(function_exists('openssl_random_pseudo_bytes'))
		{
			return openssl_random_pseudo_bytes($length);
		}

		/**
		 * Unreachable on any PHP this plugin supports.
		 *
		 * `random_bytes()` has existed since 7.0 and `openssl_random_pseudo_bytes()`
		 * since 5.3, so one of the two branches above always runs. The last resort
		 * exists only so the method cannot return short, and it uses `random_int()`
		 * rather than `mt_rand()`: in a file whose entire job is producing a nonce and
		 * an AEAD key, a non-cryptographic generator is the wrong fallback to name,
		 * even in a branch nothing can reach.
		 */
		$out = '';

		for($i = 0; $i < $length; $i++)
		{
			$out .= chr(random_int(0, 255));
		}

		return $out;
	}
}
