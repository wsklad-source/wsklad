<?php namespace Wsklad\Traits;

defined('ABSPATH') || exit;

/**
 * StoredDateTrait
 *
 * Reading the plugin's `VARCHAR` date columns, and telling the difference between
 * "never set" and "set to something unreadable".
 *
 * Split out of `DatetimeUtilityTrait` so that `Data\Abstracts\DataAbstract` can borrow
 * just this. The parent class already defines `utilityTimezoneOffset()`, and a
 * whole-trait import collides on that name; a selective import does not exclude the
 * rest of the trait, so a dedicated trait is the only way to say precisely what is
 * borrowed.
 *
 * @package Wsklad\Traits
 * @since 0.10.0
 */
trait StoredDateTrait
{
	/**
	 * Parse a stored date, or return null.
	 *
	 * `null` and `0` are different facts and conflating them is the bug this method
	 * exists to prevent:
	 *
	 * - `null` — the column was empty. Nothing to report.
	 * - `0` — the column held something that is not a date.
	 *
	 * The second case is reported, because `strtotime()` returns `false` for it and a
	 * `false` in an `: int` return becomes `0`, i.e. 1970-01-01. A row damaged by a bad
	 * migration or a truncated restore would then display a 56-year-old date as if it
	 * were real — with no exception, no log line, and no failed assertion anywhere.
	 *
	 * sparam string|null $time_string
	 * sparam int|null $from_timestamp
	 *
	 * sreturn int|null
	 */
	public function utilityStringToTimestampOrNull($time_string, $from_timestamp = null)
	{
		if(is_null($time_string) || '' === trim((string) $time_string) || '0000-00-00 00:00:00' === $time_string)
		{
			return null;
		}

		$parsed = $this->utilityParseInUtc($time_string, $from_timestamp);

		if(false === $parsed)
		{
			$this->utilityReportUnparseableDate((string) $time_string);

			return null;
		}

		return (int) $parsed;
	}

	/**
	 * Back-compatibility wrapper: same conversion, but an unreadable value is `0`.
	 *
	 * ⚠ The `0` is retained because the signature is public and changing it would be a
	 * breaking change in a `0.x` line. Use `utilityStringToTimestampOrNull()` in new
	 * code; this method exists so that existing callers keep working, and it logs so
	 * that a caller still relying on it announces the corruption.
	 *
	 * sparam string $time_string
	 * sparam int|null $from_timestamp
	 *
	 * sreturn int
	 */
	public function utilityStringToTimestamp($time_string, $from_timestamp = null): int
	{
		$parsed = $this->utilityStringToTimestampOrNull($time_string, $from_timestamp);

		return is_null($parsed) ? 0 : $parsed;
	}

	/**
	 * Run `strtotime()` in UTC, restoring the previous timezone afterwards.
	 *
	 * sparam string $time_string
	 * sparam int|null $from_timestamp
	 *
	 * sreturn int|false
	 */
	private function utilityParseInUtc($time_string, $from_timestamp = null)
	{
		$original_timezone = date_default_timezone_get();

		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set
		date_default_timezone_set('UTC');

		if(null === $from_timestamp)
		{
			$parsed = strtotime($time_string);
		}
		else
		{
			$parsed = strtotime($time_string, $from_timestamp);
		}

		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set
		date_default_timezone_set($original_timezone);

		return $parsed;
	}

	/**
	 * Announce an unreadable stored date, once per distinct value.
	 *
	 * Once per value rather than once per call: a corrupt row is read on every list
	 * render, and a log line per row per page is how a real signal gets muted.
	 *
	 * sparam string $value
	 *
	 * sreturn void
	 */
	private function utilityReportUnparseableDate(string $value)
	{
		static $reported = [];

		if(isset($reported[$value]))
		{
			return;
		}

		$reported[$value] = true;

		/**
		 * Fires when a date column holds a value that is not a date.
		 *
		 * sparam string $value The unreadable value
		 */
		do_action('wsklad_unparseable_date', $value);

		if(!function_exists('wsklad'))
		{
			return;
		}

		try
		{
			wsklad()->log()->error
			(
				'A stored date could not be parsed. It is treated as unset, not as 1970-01-01.',
				['value' => $value]
			);
		}
		catch(\Throwable $e)
		{
			// Logging must never be the thing that breaks a request.
		}
	}
}
