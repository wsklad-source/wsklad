<?php namespace Wsklad\Security;

defined('ABSPATH') || exit;

/**
 * UnavailableCryptography
 *
 * Degradation path for servers without libsodium.
 *
 * It never lies about encrypting: `encrypt()` returns an empty string and the
 * caller must keep the plain value. Reading is always safe because values that
 * carry the `v1$` envelope can only come from a server that *had* sodium — on a
 * server without it they are returned untouched rather than being destroyed, so
 * that downgrading the plugin does not lock an admin out of their own accounts.
 *
 * @package Wsklad\Security
 * @since 0.10.1
 */
final class UnavailableCryptography implements Cryptography
{
	/**
	 * @var string
	 */
	private $reason = '';

	/**
	 * @param string $reason
	 */
	public function __construct(string $reason = '')
	{
		$this->reason = $reason;
	}

	/**
	 * @return bool
	 */
	public function isAvailable(): bool
	{
		return false;
	}

	/**
	 * @return string
	 */
	public function format(): string
	{
		return '';
	}

	/**
	 * @param string $value
	 *
	 * @return bool
	 */
	public function isEncrypted(string $value): bool
	{
		return false;
	}

	/**
	 * @param string $plaintext
	 *
	 * @return string Always an empty string: the caller must keep the plain value.
	 */
	public function encrypt(string $plaintext): string
	{
		return '';
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public function decrypt(string $value): string
	{
		return $value;
	}

	/**
	 * @return string
	 */
	public function getReason(): string
	{
		return $this->reason;
	}
}
