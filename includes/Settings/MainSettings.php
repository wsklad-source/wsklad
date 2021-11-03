<?php
/**
 * Namespace
 */
namespace Wsklad\Settings;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Wsklad\Abstracts\SettingsAbstract;

/**
 * Class MainSettings
 *
 * @package Wsklad\Settings
 */
class MainSettings extends SettingsAbstract
{
	/**
	 * Main constructor.
	 */
	public function __construct()
	{
		$this->setOptionName('main');
	}
}