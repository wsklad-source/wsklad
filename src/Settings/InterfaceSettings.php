<?php namespace Wsklad\Settings;

defined('ABSPATH') || exit;

use Wsklad\Settings\Abstracts\SettingsAbstract;

/**
 * InterfaceSettings
 *
 * @package Wsklad\Settings
 */
class InterfaceSettings extends SettingsAbstract
{
	/**
	 * InterfaceSettings constructor.
	 */
	public function __construct()
	{
		$this->setOptionName('interface');
	}
}