<?php namespace Wsklad\Security;

defined('ABSPATH') || exit;

/**
 * Redactor
 *
 * Strips secrets out of anything that is about to be written to a log, an admin notice,
 * a support bundle, or an HTTP request body.
 *
 * ⚠ This has to exist *before* the first diagnostic tool, not after. A "download your
 * logs for support" button that ships before a redactor is not a support tool, it is a
 * credential disclosure with a support label on it — and the logs contain whatever was
 * in scope at the time, including request payloads and exception contexts.
 *
 * What it is not: a guarantee. It matches by key name and by known value patterns. A
 * secret stored under an unexpected key with an unexpected name will pass through. The
 * defence in depth is: encrypt at rest (0.10.1), redact on the way out (here), and never
 * put a secret in a log in the first place.
 *
 * @package Wsklad\Security
 * @since 0.10.1
 */
final class Redactor
{
	const PLACEHOLDER = '[redacted]';

	/**
	 * @var string[]
	 *
	 * Key names whose value is always a secret.
	 */
	const SECRET_KEYS =
	[
		'password',
		'passwd',
		'pwd',
		'pass',
		'token',
		'access_token',
		'refresh_token',
		'auth',
		'authorization',
		'secret',
		'client_secret',
		'api_key',
		'apikey',
		'private_key',
		'signature',
		'hash',
		'cookie',
		'nonce',
		'salt',
		'key',
		'credentials',
		'moysklad_password',
		'moysklad_token',
		'login',
	];

	/**
	 * @var string[]
	 *
	 * Substrings that mark a key as sensitive even when the exact name is unknown.
	 */
	const SECRET_KEY_SUBSTRINGS =
	[
		'password',
		'passwd',
		'secret',
		'token',
		'apikey',
		'api_key',
		'private_key',
		'authorization',
		'credential',
	];

	/**
	 * @var string[]
	 *
	 * Exact keys that merely *look* sensitive but are safe, so the redaction stays
	 * useful. A log full of `[redacted]` teaches people to ignore the marker.
	 */
	const ALLOWED_KEYS =
	[
		'key_id',
		'keyid',
		'public_key',
		'token_id',
		'has_token',
		'token_type',
		'login',
		'moysklad_login',
		'charset',
	];

	/**
	 * @var string[]
	 *
	 * Literal values registered at runtime, e.g. the current account's real token, so
	 * they are caught even when they appear under a key whose name gives nothing away
	 * (`'context' => ['body' => '…']`).
	 *
	 * @var string[]
	 */
	private static $known_values = [];

	/**
	 * @var int
	 */
	private static $max_depth = 12;

	/**
	 * Register a value that must never appear in output, in any context.
	 *
	 * @param string $value
	 *
	 * @return void
	 */
	public static function addKnownValue(string $value)
	{
		$value = trim($value);

		// Short values would match everywhere and turn the log into noise.
		if(strlen($value) < 8 || false !== strpos($value, '$'))
		{
			// Encrypted envelopes start with `v1$` and are already opaque.
			return;
		}

		if(!in_array($value, self::$known_values, true))
		{
			self::$known_values[] = $value;
		}
	}

	/**
	 * Register a decrypted credential that must never appear in output.
	 *
	 * Identical to addKnownValue() except that it does not refuse values containing `$`.
	 * That guard exists to skip encrypted envelopes, which start with `v1$` — but a plain
	 * password is allowed to contain `$` too, and a real credential that the guard skips
	 * is a credential that gets written to the log in the clear. This method therefore
	 * only accepts values the caller has already decrypted, and does not second-guess the
	 * content.
	 *
	 * The length floor stays, and for the same reason as in addKnownValue(): a one or
	 * two character value would match everywhere and turn the log to noise.
	 *
	 * @param string $value A decrypted secret, never an envelope.
	 *
	 * @return void
	 */
	public static function addKnownSecret(string $value)
	{
		$value = trim($value);

		if(strlen($value) < 8)
		{
			return;
		}

		if(!in_array($value, self::$known_values, true))
		{
			self::$known_values[] = $value;
		}
	}

	/**
	 * Forget all registered values. Used by tests.
	 *
	 * @return void
	 */
	public static function reset()
	{
		self::$known_values = [];
	}

	/**
	 * Redact a string.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public static function text(string $value): string
	{
		if('' === $value)
		{
			return $value;
		}

		$replacements = [];

		foreach(self::$known_values as $known)
		{
			$replacements[$known] = self::PLACEHOLDER;
		}

		// Moy Sklad tokens and the plugin's own encryption envelope.
		$replacements = $replacements + [
			'v1$' => 'v1$' . self::PLACEHOLDER . '$',
		];

		if(empty($replacements))
		{
			return $value;
		}

		$search  = array_keys($replacements);
		$replace = array_values($replacements);

		return str_replace($search, $replace, $value);
	}

	/**
	 * Redact a value of any shape, recursively.
	 *
	 * @param mixed $value
	 * @param int $depth
	 *
	 * @return mixed
	 */
	public static function value($value, int $depth = 0)
	{
		if($depth > self::$max_depth)
		{
			return self::PLACEHOLDER;
		}

		if(is_string($value))
		{
			return self::text($value);
		}

		if(is_array($value))
		{
			$out = [];

			foreach($value as $key => $item)
			{
				$out[$key] = self::isSecretKey((string) $key) ? self::PLACEHOLDER : self::value($item, $depth + 1);
			}

			return $out;
		}

		if($value instanceof \Throwable)
		{
			return self::exception($value);
		}

		if(is_object($value))
		{
			// Never walk an arbitrary object graph: a DTO can hold a live connection.
			// Its class name is the useful part.
			return '[object ' . get_class($value) . ']';
		}

		return $value;
	}

	/**
	 * Redact an exception, including its context, without leaking the arguments that
	 * produced it.
	 *
	 * @param \Throwable $e
	 *
	 * @return array
	 */
	public static function exception(\Throwable $e): array
	{
		return
		[
			'type' => get_class($e),
			'message' => self::text($e->getMessage()),
			'file' => self::text($e->getFile()),
			'line' => $e->getLine(),
			'trace' => self::text($e->getTraceAsString()),
		];
	}

	/**
	 * Redact a whole log record.
	 *
	 * @param array $record
	 *
	 * @return array
	 */
	public static function record(array $record): array
	{
		$out = [];

		foreach($record as $key => $value)
		{
			if('context' === $key || 'extra' === $key)
			{
				$out[$key] = self::value($value);

				continue;
			}

			if(in_array($key, ['datetime', 'level', 'level_name', 'channel', 'message'], true))
			{
				$out[$key] = is_string($value) ? self::text($value) : $value;

				continue;
			}

			$out[$key] = self::isSecretKey((string) $key) ? self::PLACEHOLDER : self::value($value);
		}

		return $out;
	}

	/**
	 * Whether a key name means the value must never be shown.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public static function isSecretKey(string $key): bool
	{
		$normalised = strtolower(str_replace(['-', ' '], '_', $key));

		if(in_array($normalised, self::ALLOWED_KEYS, true))
		{
			return false;
		}

		if(in_array($normalised, self::SECRET_KEYS, true))
		{
			return true;
		}

		foreach(self::SECRET_KEY_SUBSTRINGS as $needle)
		{
			if(false !== strpos($normalised, $needle))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Redact an HTTP header bag.
	 *
	 * @param array $headers
	 *
	 * @return array
	 */
	public static function headers(array $headers): array
	{
		return self::value($headers);
	}
}
