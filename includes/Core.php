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
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Wsklad\Interfaces\SettingsInterface;
use Wsklad\Log\CoreLog;
use Wsklad\Settings\MainSettings;
use Wsklad\Traits\Singleton;

/**
 * Class Core
 *
 * @package Wsklad
 */
class Core
{
	use Singleton;
	use LoggerAwareTrait;

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

		// localization files
		wsklad_load_textdomain();

		// hook
		do_action(WSKLAD_PREFIX . 'after_init');
	}

	/**
	 * Log
	 *
	 * @return LoggerInterface
	 */
	public function log()
	{
		if(is_null($this->log))
		{
			$this->log = new CoreLog();
		}

		return $this->log;
	}

	/**
	 * Get settings
	 *
	 * @return MainSettings
	 * @throws Exception
	 */
	public function settings()
	{
		if(!$this->settings instanceof SettingsInterface)
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

		return $this->settings;
	}
}