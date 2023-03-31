<?php namespace Wsklad\Settings;

defined('ABSPATH') || exit;

use Wsklad\Abstracts\SettingsAbstract;

/**
 * LogsSettings
 *
 * @package Wsklad\Settings
 */
class LogsSettings extends SettingsAbstract
{
	/**
	 * LogsSettings constructor.
	 */
	public function __construct()
	{
		$this->setOptionName('logs');
	}
}