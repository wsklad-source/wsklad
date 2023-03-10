<?php namespace Wsklad\Settings\Abstracts;

defined('ABSPATH') || exit;

/**
 * SettingsAbstract
 *
 * @package Wsklad\Settings
 */
abstract class SettingsAbstract extends \Digiom\Woplucore\Abstracts\SettingsAbstract
{
	/**
	 * @var string Name option prefix in wp_options
	 */
	protected $option_name_prefix = 'wsklad_settings_';
}