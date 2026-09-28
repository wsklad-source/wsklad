<?php namespace Wsklad\Security;

defined('ABSPATH') || exit;

/**
 * Cryptography
 *
 * Contract for reversible encryption of sensitive values (Moy Sklad credentials).
 * Every implementation must be able to report whether a stored value is
 * already encrypted, so that transparent migration on read is possible.
 *
 * @package Wsklad\Security
 * @since 0.10.1
 */
interface Cryptography
{
	/**
	 * Encrypt a plain text value.
	 *
	 * @param string $plaintext
	 *
	 * @return string Cipher text in `$this->format()` prefixed envelope, or empty string on failure
	 */
	public function encrypt(string $plaintext): string;

	/**
	 * Decrypt a value previously produced by encrypt().
	 *
	 * Non-encrypted (legacy plain text) input must be returned as is, so that
	 * reading an account written before the migration never fails.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function decrypt(string $value): string;

	/**
	 * Whether the value carries this implementation's envelope marker.
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public function isEncrypted(string $value): bool;

	/**
	 * Envelope prefix, e.g. `v1$`.
	 *
	 * @return string
	 */
	public function format(): string;

	/**
	 * Whether this implementation can actually encrypt on this server.
	 *
	 * @return bool
	 */
	public function isAvailable(): bool;
}
