<?php namespace Wsklad\Traits;

defined('ABSPATH') || exit;

/**
 * DatetimeUtilityTrait
 *
 * Site-timezone helpers for display. The *parsing* half lives in `StoredDateTrait`,
 * which this borrows: date reading and date formatting fail in opposite directions and
 * keeping them in one file invites exactly the confusion this split avoids — a reader
 * who sees a parse failure assume a formatting problem, or the reverse.
 *
 * @package Wsklad\Traits
 */
trait DatetimeUtilityTrait
{
	use StoredDateTrait;

	/**
	 * Helper to retrieve the timezone string for a site until
	 *
	 * @return string PHP timezone string for the site
	 */
	public function utilityTimezoneString(): string
    {
		// If site timezone string exists, return it
		$timezone = get_option('timezone_string');

		if($timezone)
		{
			return $timezone;
		}

		// Get UTC offset, if it isn't set then return UTC
		$utc_offset = (int) get_option('gmt_offset', 0);
		if(0 === $utc_offset)
		{
			return 'UTC';
		}

		// Adjust UTC offset from hours to seconds
		$utc_offset *= 3600;

		// Attempt to guess the timezone string from the UTC offset
		$timezone = timezone_name_from_abbr('', $utc_offset);
		if($timezone)
		{
			return $timezone;
		}

		// Last try, guess timezone string manually
		foreach(timezone_abbreviations_list() as $abbr)
		{
			foreach($abbr as $city)
			{
				// WordPress restrict the use of date(), since it's affected by timezone settings, but in this case is just what we need to guess the correct timezone
				// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                if((bool) date('I') === (bool) $city['dst'] && $city['timezone_id'] && (int) $city['offset'] === $utc_offset)
				{
					return $city['timezone_id'];
				}
			}
		}

		return 'UTC';
	}

    /**
     * Get timezone offset in seconds
     *
     * @return float
     * @throws \DateInvalidTimeZoneException
     */
	public function utilityTimezoneOffset()
	{
		$timezone = get_option('timezone_string');

		if($timezone)
		{
			return (new \DateTimeZone($timezone))->getOffset(new \DateTime('now'));
		}

		return (float) get_option('gmt_offset', 0) * HOUR_IN_SECONDS;
	}

	/**
	 * @param $date
	 *
	 * @return string
	 */
	public function utilityPrettyDate($date): string
    {
		if(!$date)
		{
			return esc_html__('not', 'wsklad');
		}

		$timestamp_create = $this->utilityStringToTimestamp($date) + $this->utilityTimezoneOffset();

		return sprintf
		(
			'%s <span class="time">%s: %s</span>',
			date_i18n('d/m/Y', $timestamp_create),
            esc_html__('in', 'wsklad'),
			date_i18n('H:i:s', $timestamp_create)
		);
	}
}
