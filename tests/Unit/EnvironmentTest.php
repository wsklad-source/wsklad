<?php namespace Wsklad\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wsklad\Security\KeyProvider;
use Wsklad\Testing\WordPress;

/**
 * EnvironmentTest
 *
 * `KeyProvider` derives the 32-byte key that encrypts every stored Moy Sklad
 * credential. Two sites running the same WordPress, with the same
 * `AUTH_KEY` copied from a tutorial, must not end up with the same key — so the
 * derivation mixes in a per-install salt, and on multisite the blog id as well.
 *
 * The property that makes this worth a test is determinism. A key that changes
 * between two calls in the same request is not a slow leak, it is a total loss:
 * the value encrypted earlier in the request cannot be read back later in it, and
 * the failure surfaces as an authentication error against Moy Sklad, on a
 * different page, with no stack trace pointing here.
 *
 * @package Wsklad\Tests\Unit
 */
class EnvironmentTest extends TestCase
{
	/**
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();

		WordPress::reset();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		WordPress::reset();

		parent::tearDown();
	}

	/**
	 * The same inputs derive the same key.
	 *
	 * @return void
	 */
	public function test_derive_key_is_deterministic(): void
	{
		$provider = new KeyProvider();

		$first  = $provider->deriveKey();
		$second = $provider->deriveKey();

		$this->assertSame
		(
			$first,
			$second,
			'deriveKey() returned a different key for the same installation on two consecutive calls. '
			. 'A credential encrypted with the first key cannot be decrypted with the second.'
		);
	}

	/**
	 * Two independent providers on the same install agree.
	 *
	 * This is the form that matters in production: the encryption helper and the
	 * decryption helper are not the same object, and they are often not even built
	 * in the same request.
	 *
	 * @return void
	 */
	public function test_two_providers_on_the_same_install_agree(): void
	{
		$first  = (new KeyProvider())->deriveKey();
		$second = (new KeyProvider())->deriveKey();

		$this->assertSame($first, $second);
	}

	/**
	 * The derived key is 32 bytes.
	 *
	 * Not 31, not 64. XChaCha20-Poly1305 takes a 32-byte key; anything else is
	 * rejected by libsodium, and a 64-byte "key" that happens to be accepted
	 * elsewhere means two different derivations are in play.
	 *
	 * @return void
	 */
	public function test_derived_key_is_32_bytes(): void
	{
		$key = (new KeyProvider())->deriveKey();

		$this->assertIsString($key);
		$this->assertSame(32, strlen($key), 'The key must be exactly 32 bytes, got ' . strlen($key) . '.');
		$this->assertSame(32, KeyProvider::SALT_BYTES, 'The salt length and the key length must agree.');
	}

	/**
	 * A different install salt gives a different key.
	 *
	 * Two WordPress sites with identical `wp-config.php` salts — a very common
	 * copy-paste, and the default in a lot of managed hosting — must not share an
	 * encryption key. The per-install salt is the only thing standing between
	 * them, so this is the assertion that proves it is wired in.
	 *
	 * @return void
	 */
	public function test_a_different_install_salt_gives_a_different_key(): void
	{
		WordPress::$site_options[KeyProvider::SALT_OPTION] = str_repeat('a', 32);

		$first = (new KeyProvider())->deriveKey();

		WordPress::$site_options[KeyProvider::SALT_OPTION] = str_repeat('b', 32);

		$second = (new KeyProvider())->deriveKey();

		$this->assertNotSame
		(
			$first,
			$second,
			'The install salt is not part of the derivation: two sites with the same wp-config.php salts '
			. 'would share one encryption key.'
		);
	}

	/**
	 * The derivation matches HKDF-SHA256 as specified.
	 *
	 * The expected key is computed here from first principles — the input key
	 * material, the info string and the salt — rather than by calling the code
	 * under test. That is what makes this an assertion rather than a tautology.
	 *
	 * In the test environment no WordPress salts are defined, so `salts()` falls
	 * back to its documented constant, and the blog id is 1 on a single site.
	 *
	 * @return void
	 */
	public function test_derivation_matches_hkdf_sha256(): void
	{
		$salt = str_repeat('k', 32);

		WordPress::$site_options[KeyProvider::SALT_OPTION] = $salt;

		$actual = (new KeyProvider())->deriveKey();

		$expected = hash_hkdf
		(
			'sha256',
			'wsklad-no-wp-salts',
			32,
			'wsklad-account-credentials|v1|blog=1',
			$salt
		);

		$this->assertSame
		(
			$expected,
			$actual,
			'deriveKey() does not match HKDF-SHA256 over the documented inputs. If the info string or the '
			. 'input key material changed, every credential already in the database becomes unreadable.'
		);
	}

	/**
	 * Blogs on one multisite network get different keys.
	 *
	 * @return void
	 */
	public function test_multisite_blogs_are_separated(): void
	{
		WordPress::$site_options[KeyProvider::SALT_OPTION] = str_repeat('k', 32);

		$first = (new KeyProvider())->deriveKey();

		WordPress::$multisite = true;

		$second = (new KeyProvider())->deriveKey();

		$this->assertNotSame
		(
			$first,
			$second,
			'On multisite the blog id must be mixed into the info string, or every blog on the network '
			. 'shares one key.'
		);
	}

	/**
	 * The key is binary, not hex.
	 *
	 * 32 hex characters would be 16 bytes of entropy wearing 32 bytes of
	 * costume. `strlen()` alone cannot tell the difference, so the alphabet is
	 * checked too.
	 *
	 * @return void
	 */
	public function test_key_is_binary_rather_than_hex(): void
	{
		WordPress::$site_options[KeyProvider::SALT_OPTION] = str_repeat('k', 32);

		$key = (new KeyProvider())->deriveKey();

		$this->assertSame(1, preg_match('/[^0-9a-f]/', $key), 'The key looks like hex, i.e. it is 16 bytes of entropy.');
	}

	/**
	 * The stored salt has the length the provider insists on.
	 *
	 * `getSalt()` rejects anything that is not exactly `SALT_BYTES` long and
	 * generates a new one instead. If the generator cannot produce a salt of that
	 * length, the provider rewrites the salt on every single call and derives a
	 * different key every time — deterministic in name only.
	 *
	 * @return void
	 */
	public function test_stored_salt_has_the_expected_length(): void
	{
		$provider = new KeyProvider();

		$provider->deriveKey();

		$stored = (string) get_site_option(KeyProvider::SALT_OPTION, '');

		$this->assertSame
		(
			KeyProvider::SALT_BYTES,
			strlen($stored),
			'The install salt written to the options table is ' . strlen($stored) . ' bytes, but getSalt() '
			. 'only accepts ' . KeyProvider::SALT_BYTES . '. It will therefore regenerate and re-store the salt '
			. 'on every call, and deriveKey() will not be reproducible.'
		);
	}

	/**
	 * A salt of the right length is reused rather than replaced.
	 *
	 * @return void
	 */
	public function test_a_valid_salt_is_reused(): void
	{
		$salt = str_repeat('k', 32);

		WordPress::$site_options[KeyProvider::SALT_OPTION] = $salt;

		$provider = new KeyProvider();

		$this->assertSame($salt, $provider->getSalt());
		$this->assertSame($salt, $provider->getSalt());
		$this->assertSame($salt, get_site_option(KeyProvider::SALT_OPTION), 'A valid salt must not be rewritten.');
	}

	/**
	 * A salt of the wrong length is replaced.
	 *
	 * @return void
	 */
	public function test_an_invalid_salt_is_replaced(): void
	{
		WordPress::$site_options[KeyProvider::SALT_OPTION] = 'too-short';

		$salt = (new KeyProvider())->getSalt();

		$this->assertSame(KeyProvider::SALT_BYTES, strlen($salt), 'A replacement salt of the wrong length is no fix.');
		$this->assertSame($salt, get_site_option(KeyProvider::SALT_OPTION));
	}

	/**
	 * The provider reports whether encryption is possible here.
	 *
	 * @return void
	 */
	public function test_availability_is_reported_consistently(): void
	{
		$expected = function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt');

		$this->assertSame($expected, (new KeyProvider())->isAvailable());
		$this->assertSame($expected, (new KeyProvider())->get()->isAvailable());
	}

	/**
	 * The key id round trips, and rotation advances it.
	 *
	 * @return void
	 */
	public function test_key_id_round_trip_and_rotation(): void
	{
		$provider = new KeyProvider();

		$this->assertSame('k1', $provider->getKeyId(), 'An unset key id must fall back to k1.');

		$this->assertSame('k2', $provider->rotateKeyId());
		$this->assertSame('k2', get_site_option(KeyProvider::KEY_ID_OPTION));
		$this->assertSame('k2', $provider->getKeyId());
	}
}
