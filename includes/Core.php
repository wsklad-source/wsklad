<?php
/**
 * Namespace
 */
namespace Wsklad;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Exception;
use Wsklad\Settings\MainSettings;
use Wsklad\Traits\Singleton;

/**
 * Class Core
 *
 * @package Wsklad
 */
class Core
{
	/**
	 * Traits
	 */
	use Singleton;

	/**
	 * Settings
	 *
	 * @var MainSettings
	 */
	private $settings;

	/**
	 * Core constructor
	 */
	public function __construct()
	{
		// hook
		do_action(WSKLAD_PREFIX . 'before_loading');

		// localization files
		wsklad_load_textdomain();

		// init
		add_action('init', [$this, 'init'], 3);

		// admin
		if(false !== is_admin())
		{
			add_action('init', [Admin::class, 'instance'], 5);
		}

		// hook
		do_action(WSKLAD_PREFIX . 'after_loading');
	}

	/**
	 * Initialization
	 *
	 * @return void
	 */
	public function init()
	{
		// hook
		do_action(WSKLAD_PREFIX . 'before_init');

		try
		{
			$this->load_settings();
		}
		catch(Exception $e)
		{}

		// hook
		do_action(WSKLAD_PREFIX . 'after_init');
	}

	/**
	 * Load main settings
	 *
	 * @return void
	 * @throws Exception
	 */
	private function load_settings()
	{
		try
		{
			$settings = new MainSettings();
			$settings->init();
		}
		catch(Exception $e)
		{
			throw new Exception('load_settings: exception - ' . $e->getMessage());
		}

		$this->settings = $settings;
	}

	/**
	 * Get settings
	 *
	 * @return MainSettings
	 */
	public function settings()
	{
		return $this->settings;
	}
}