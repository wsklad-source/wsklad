<?php namespace Wsklad\Log;

defined('ABSPATH') || exit;

use Monolog\Logger as Monolog;

/**
 * Logger
 *
 * @package Wsklad
 */
final class Logger extends Monolog
{
	/**
	 * @var string
	 */
	protected $name = 'main';
}