<?php namespace Wsklad\Admin\Wizards;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;

/**
 * Init
 *
 * @package Wsklad\Admin\Wizards
 */
final class Init
{
	use SingletonTrait;

	/**
	 * Init constructor.
	 */
	public function __construct()
	{
		/**
		 * Setup
		 */
		if('setup' === get_option('wsklad_wizard', false))
		{
			SetupWizard::instance();
		}

		/**
		 * Update
		 */
		if('update' === get_option('wsklad_wizard', false))
		{
			UpdateWizard::instance();
		}
	}
}