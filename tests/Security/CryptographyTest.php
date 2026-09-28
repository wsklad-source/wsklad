<?php namespace Wsklad\Tests\Security;

use PHPUnit\Framework\TestCase;
use Wsklad\Security\SodiumCryptography;
use Wsklad\Security\UnavailableCryptography;

/**
 * CryptographyTest
 *
 * WSKLAD stores a Moy Sklad password and an API token. Both are bearer
 * credentials: whoever reads them can act as the store owner, including placing
 * orders and reading customer data. They are encrypted at rest with
 * XChaCha20-Poly1305, so these tests are about one property above all — a
 * ciphertext that has been altered must not come back as a plausible plaintext.
 *
 * AEAD's whole guarantee is that. If `decrypt()` returned the plaintext anyway
 * after a failed authentication, an attacker with write access to the options
 * table could rewrite a token and the plugin would use it, with no error
 * anywhere. So tamper detection is asserted separately from, and before, any
 * convenience check.
 *
 * The format assertions (`isEncrypted()`) do not need libsodium and always run,
 * including on a host without the extension: a server that cannot encrypt must
 * still recognise what a server that could wrote, or downgrading the plugin
 * silently mangles every stored credential.
 *
 * @package Wsklad\Tests\Security
 */
class CryptographyTest extends TestCase
{
	/**
	 * 32 bytes, as XChaCha20-Poly1305 requires.
	 *
	 * @var string
	 */
	private $key;

	/**
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();

		// A fixed key: a test that generates a fresh random key every run cannot
		// be re-run against a stored value, and cannot be debugged.
		$this->key = hash('sha256', 'wsklad-cryptography-test-key', true);
	}

	/* ---------------------------------------------------------------------
	 * Round trip
	 * ------------------------------------------------------------------ */

	/**
	 * Plain values survive a full encrypt/decrypt round trip.
	 *
	 * @return void
	 */
	public function test_round_trip_for_ascii(): void
	{
		$crypto = new SodiumCryptography($this->key, 'k1');
		$plain  = 'moysklad-password-123';

		$encrypted = $crypto->encrypt($plain);

		$this->assertNotSame('', $encrypted, 'encrypt() returned nothing.');
		$this->assertNotSame($plain, $encrypted, 'encrypt() returned the plaintext.');
		$this->assertSame($plain, $crypto->decrypt($encrypted));
	}

	/**
	 * Multibyte input survives — the values are not always ASCII.
	 *
	 * @return void
	 */
	public function test_round_trip_for_cyrillic(): void
	{
		$crypto = $this->sodium();
		$plain  = 'Пароль-123-秘密-Ω';

		$this->assertSame($plain, $crypto->decrypt($crypto->encrypt($plain)));
	}

	/**
	 * The empty string is not an error, and not an encryption.
	 *
	 * @return void
	 */
	public function test_round_trip_for_empty_string(): void
	{
		$crypto = $this->sodium();

		$this->assertSame('', $crypto->encrypt(''), 'encrypt("") must stay empty, not become an envelope.');
		$this->assertSame('', $crypto->decrypt(''), 'decrypt("") must stay empty.');
	}

	/**
	 * A 1 KB value — the size of an embedded document, say — round trips.
	 *
	 * @return void
	 */
	public function test_round_trip_for_one_kilobyte(): void
	{
		$crypto = $this->sodium();
		$plain  = str_repeat('the quick brown fox jumps over the lazy dog. ', 23); // 1035 bytes

		$this->assertSame(1035, strlen($plain));

		$encrypted = $crypto->encrypt($plain);

		$this->assertSame($plain, $crypto->decrypt($encrypted));
	}

	/**
	 * Binary input with NUL bytes is not truncated.
	 *
	 * A C-string assumption anywhere in the stack silently cuts a credential in
	 * half, and the failure is a 401 from Moy Sklad rather than a bug report.
	 *
	 * @return void
	 */
	public function test_round_trip_for_binary_data_with_nul_bytes(): void
	{
		$crypto = $this->sodium();
		$plain  = "before\0after\0\xff\xfe\x01\x7f";

		$this->assertSame($plain, $crypto->decrypt($crypto->encrypt($plain)));
	}

	/**
	 * The envelope survives a trip through base64, which is how it crosses a
	 * JSON API or a CLI argument.
	 *
	 * @return void
	 */
	public function test_envelope_survives_base64(): void
	{
		$crypto = $this->sodium();
		$plain  = 'secret-token-123';

		$encoded = base64_encode($crypto->encrypt($plain));

		$this->assertNotFalse(base64_decode($encoded, true), 'The envelope is not valid base64.');

		$this->assertSame($plain, $crypto->decrypt((string) base64_decode($encoded, true)));
	}

	/* ---------------------------------------------------------------------
	 * Tamper detection
	 * ------------------------------------------------------------------ */

	/**
	 * Flipping one bit of the ciphertext destroys the value instead of
	 * returning a corrupt plaintext.
	 *
	 * @return void
	 */
	public function test_tampered_ciphertext_does_not_decrypt(): void
	{
		$crypto = $this->sodium();
		$plain  = 'secret-token-123';

		$encrypted = $crypto->encrypt($plain);
		$parts     = explode('$', $encrypted);

		$cipher = (string) base64_decode($parts[3], true);
		$cipher[5] = chr(ord($cipher[5]) ^ 0x01);
		$parts[3] = base64_encode($cipher);

		$tampered = $crypto->decrypt(implode('$', $parts));

		$this->assertSame('', $tampered, 'A tampered ciphertext must decrypt to an empty string, never to plaintext.');
		$this->assertNotSame($plain, $tampered, 'A tampered ciphertext must never yield the original plaintext.');
		$this->assertNotSame('', $crypto->getError(), 'Authentication failure must be recorded, not swallowed.');
	}

	/**
	 * Tampering with the nonce is detected the same way.
	 *
	 * @return void
	 */
	public function test_tampered_nonce_does_not_decrypt(): void
	{
		$crypto   = $this->sodium();
		$plain    = 'secret-token-123';
		$encrypted = $crypto->encrypt($plain);

		$parts = explode('$', $encrypted);
		$nonce = (string) base64_decode($parts[2], true);
		$nonce[0] = chr(ord($nonce[0]) ^ 0x01);
		$parts[2] = base64_encode($nonce);

		$this->assertSame('', $crypto->decrypt(implode('$', $parts)));
	}

	/**
	 * A ciphertext opened with the wrong key returns nothing.
	 *
	 * @return void
	 */
	public function test_wrong_key_does_not_decrypt(): void
	{
		$encrypted = $this->sodium()->encrypt('secret-token-123');

		$wrong = new SodiumCryptography(hash('sha256', 'a different key', true), 'k1');

		$this->assertSame('', $wrong->decrypt($encrypted));
	}

	/**
	 * A payload that is not base64 is rejected rather than decoded into rubbish.
	 *
	 * @return void
	 */
	public function test_malformed_base64_is_rejected(): void
	{
		$crypto = $this->sodium();

		$this->assertSame('', $crypto->decrypt('v1$k1$!!!$!!!'));
		$this->assertNotSame('', $crypto->getError());
	}

	/* ---------------------------------------------------------------------
	 * Format
	 * ------------------------------------------------------------------ */

	/**
	 * A real envelope is recognised.
	 *
	 * @return void
	 */
	public function test_is_encrypted_true_for_a_real_envelope(): void
	{
		$crypto = $this->sodium();

		$encrypted = $crypto->encrypt('secret-token-123');

		$this->assertTrue($crypto->isEncrypted($encrypted));
		$this->assertStringStartsWith('v1$', $encrypted);
		$this->assertSame('v1$', $crypto->format());
		$this->assertSame(4, count(explode('$', $encrypted)), 'v1$<key_id>$<nonce>$<cipher>');
	}

	/**
	 * Plain legacy values are not mistaken for envelopes.
	 *
	 * This is what makes the 0.10.1 migration safe: rows written before it hold
	 * plain text, and treating them as ciphertext would destroy every account on
	 * upgrade.
	 *
	 * @return void
	 */
	public function test_is_encrypted_false_for_plain_text(): void
	{
		$crypto = $this->sodium();

		$this->assertFalse($crypto->isEncrypted('plain-password'));
		$this->assertFalse($crypto->isEncrypted(''));
		$this->assertFalse($crypto->isEncrypted('eyJpdiI6IjEifQ=='));
	}

	/**
	 * A value that merely contains `$` is not an envelope.
	 *
	 * @return void
	 */
	public function test_is_encrypted_false_for_a_value_containing_a_dollar_sign(): void
	{
		$crypto = $this->sodium();

		$this->assertFalse($crypto->isEncrypted('$'));
		$this->assertFalse($crypto->isEncrypted('a$b'));
		$this->assertFalse($crypto->isEncrypted('price$100'));
		$this->assertFalse($crypto->isEncrypted('v1'), 'The prefix alone is not an envelope.');
		$this->assertFalse($crypto->isEncrypted('v1$k1'), 'Two segments are not an envelope.');
	}

	/**
	 * A legacy plain value is returned unchanged, not destroyed.
	 *
	 * @return void
	 */
	public function test_legacy_plain_text_passes_through_decrypt(): void
	{
		$crypto = $this->sodium();

		$this->assertSame('legacy-plain', $crypto->decrypt('legacy-plain'));
	}

	/**
	 * Every ciphertext is different: the nonce is random, not derived.
	 *
	 * Repeating a nonce under the same key destroys the security of the whole
	 * file, so this is a security property, not a curiosity.
	 *
	 * @return void
	 */
	public function test_two_encryptions_of_the_same_value_differ(): void
	{
		$crypto = $this->sodium();

		$first  = $crypto->encrypt('same-value');
		$second = $crypto->encrypt('same-value');

		$this->assertNotSame($first, $second, 'The nonce is not random; the same plaintext produced the same ciphertext.');
		$this->assertSame('same-value', $crypto->decrypt($first));
		$this->assertSame('same-value', $crypto->decrypt($second));
	}

	/* ---------------------------------------------------------------------
	 * Availability
	 * ------------------------------------------------------------------ */

	/**
	 * The host is asked, not assumed.
	 *
	 * @return void
	 */
	public function test_sodium_reports_its_own_availability(): void
	{
		$expected = function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt');

		$this->assertSame($expected, SodiumCryptography::hasS());
		$this->assertSame($expected, (new SodiumCryptography($this->key))->isAvailable());
	}

	/* ---------------------------------------------------------------------
	 * The degradation path
	 * ------------------------------------------------------------------ */

	/**
	 * Without libsodium the implementation says so, and encrypts nothing.
	 *
	 * It must not return a reversible-but-weak cipher: a host that cannot do
	 * XChaCha20-Poly1305 should keep the plain value and tell the admin, not
	 * quietly store something that looks encrypted.
	 *
	 * @return void
	 */
	public function test_unavailable_implementation_never_claims_to_encrypt(): void
	{
		$crypto = new UnavailableCryptography('ext-sodium is not loaded');

		$this->assertFalse($crypto->isAvailable());
		$this->assertSame('', $crypto->encrypt('secret-token-123'));
		$this->assertSame('', $crypto->format());
		$this->assertSame('ext-sodium is not loaded', $crypto->getReason());
	}

	/**
	 * Without libsodium, values written by a host that had it are passed through.
	 *
	 * Destroying them would lock an admin out of their own accounts the moment
	 * the extension is disabled — the worst possible failure mode for a
	 * degradation path.
	 *
	 * @return void
	 */
	public function test_unavailable_implementation_preserves_undecryptable_values(): void
	{
		$crypto = new UnavailableCryptography();

		$envelope = 'v1$k1$YWJjZGVmZ2g=$aGVsbG8gd29ybGQ=';

		$this->assertSame($envelope, $crypto->decrypt($envelope), 'The envelope must survive untouched.');
		$this->assertSame('plain', $crypto->decrypt('plain'));
		$this->assertFalse($crypto->isEncrypted($envelope), 'It cannot claim to have decrypted it.');
	}

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------ */

	/**
	 * A sodium instance, or a skip with a reason a human can act on.
	 *
	 * @return SodiumCryptography
	 */
	private function sodium(): SodiumCryptography
	{
		if(!SodiumCryptography::hasS())
		{
			$this->markTestSkipped
			(
				'ext-sodium (sodium_crypto_aead_xchacha20poly1305_ietf_encrypt) is not available on this '
				. 'PHP build, so XChaCha20-Poly1305 cannot be exercised. The envelope-format and '
				. 'UnavailableCryptography tests in this class do not need it and still run.'
			);
		}

		return new SodiumCryptography($this->key, 'k1');
	}
}
