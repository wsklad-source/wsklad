<?php
/**
 * Namespace
 */
namespace Wsklad\Log;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use \Monolog\Logger as Monolog;

/**
 * Class CoreLog
 *
 * @package Wsklad
 */
class CoreLog extends Monolog
{
	/**
	 * @var string
	 */
	protected $name = 'core';

	/**
	 * CoreLog constructor.
	 *
	 * @param array $handlers
	 * @param array $processors
	 */
	public function __construct(array $handlers = [], array $processors = [])
	{
		parent::__construct($this->name, $handlers, $processors);
	}
}