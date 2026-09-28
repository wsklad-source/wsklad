<?php namespace Wsklad\Log;

defined('ABSPATH') || exit;

use Monolog\Processor\UidProcessor;
use Wsklad\Security\Redactor;

/**
 * Processor
 *
 * Runs on every record before it reaches a handler, and is the single choke point that
 * guarantees nothing sensitive reaches a log file.
 *
 * The redaction lives here rather than in the formatter or the handler on purpose:
 * Monolog lets an extension push its own handler onto the same logger (the
 * `wsklad_log_load_before` filter exists for exactly that), and a redactor in the
 * formatter would be bypassed by any of them. A processor is the only position every
 * record passes through regardless of where it is eventually written.
 *
 * @package Wsklad
 */
final class Processor extends UidProcessor
{
	/**
	 * @param array $record
	 *
	 * @return array
	 */
	public function __invoke(array $record): array
	{
		$record = parent::__invoke($record);

		return Redactor::record($record);
	}
}
