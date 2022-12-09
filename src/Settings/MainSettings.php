<?php namespace Wsklad\Settings;

defined('ABSPATH') || exit;

use Wsklad\Settings\Abstracts\SettingsAbstract;

/**
 * Class MainSettings
 *
 * @package Wsklad\Settings
 */
class MainSettings extends SettingsAbstract implements \Wsklad\Interfaces\SettingsInterface
{
	/**
	 * Main constructor.
	 */
	public function __construct()
	{
		$this->setOptionName('main');
	}
}