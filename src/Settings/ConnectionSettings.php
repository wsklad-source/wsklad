<?php namespace Wsklad\Settings;

defined('ABSPATH') || exit;

use Wsklad\Abstracts\SettingsAbstract;

/**
 * ConnectionSettings
 *
 * @package Wsklad\Settings
 */
class ConnectionSettings extends SettingsAbstract
{
	/**
	 * ConnectionSettings constructor.
	 */
	public function __construct()
	{
		$this->setOptionName('connection');
	}
}