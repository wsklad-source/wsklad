<?php namespace Wsklad;

defined('ABSPATH') || exit;

use wpdb;
use Digiom\Woplucore\Interfaces\SettingsInterface;
use Digiom\Woplucore\Abstracts\CoreAbstract;
use Digiom\Woplucore\Traits\SingletonTrait;
use Psr\Log\LoggerInterface;
use Wsklad\Exceptions\Exception;
use Wsklad\Log\Formatter;
use Wsklad\Log\Handler;
use Wsklad\Log\Logger;
use Wsklad\Log\Processor;
use Wsklad\Settings\InterfaceSettings;
use Wsklad\Settings\LogsSettings;
use Wsklad\Settings\MainSettings;

/**
 * Core
 *
 * @package Wsklad
 */
final class Core extends CoreAbstract
{
	use SingletonTrait;

	/**
	 * @var array
	 */
	private $log = [];

	/**
	 * @var Timer
	 */
	private $timer;

	/**
	 * @var SettingsInterface
	 */
	private $settings = [];

	/**
	 * @var Tecodes\Client
	 */
	private $tecodes;

	/**
	 * Core constructor.
	 *
	 * @return void
	 */
	public function __construct()
	{
		do_action('wsklad_core_loaded');
	}

	/**
	 * Initialization
	 */
	public function init()
	{
		// hook
		do_action('wsklad_before_init');

		$this->localization();

		try
		{
			$this->timer();
		}
		catch(\Throwable $e)
		{
			wsklad()->log()->alert(__('Timer not loaded.', 'wsklad'), ['exception' => $e]);
			return;
		}

		try
		{
			$this->extensions()->load();
		}
		catch(\Throwable $e)
		{
			wsklad()->log()->alert(__('Extensions not loaded.', 'wsklad'), ['exception' => $e]);
		}

		try
		{
			$this->extensions()->init();
		}
		catch(\Throwable $e)
		{
			wsklad()->log()->alert(__('Extensions not initialized.', 'wsklad'), ['exception' => $e]);
		}

		try
		{
			$this->tools()->load();
		}
		catch(\Throwable $e)
		{
			wsklad()->log()->alert(__('Tools not loaded.', 'wsklad'), ['exception' => $e]);
		}

		if(false !== wsklad()->context()->isAdmin())
		{
			try
			{
				$this->tools()->init();
			}
			catch(\Throwable $e)
			{
				wsklad()->log()->alert(__('Tools not initialized.', 'wsklad'), ['exception' => $e]);
			}
		}

		// hook
		do_action('wsklad_after_init');
	}

	/**
	 * Extensions
	 *
	 * @return Extensions\Core
	 */
	public function extensions(): Extensions\Core
	{
		return Extensions\Core::instance();
	}

	/**
	 * Filesystem
	 *
	 * @return Filesystem
	 */
	public function filesystem(): Filesystem
	{
		return Filesystem::instance();
	}

	/**
	 * Environment
	 *
	 * @return Environment
	 */
	public function environment(): Environment
	{
		return Environment::instance();
	}

	/**
	 * Views
	 *
	 * @return Views
	 */
	public function views(): Views
	{
		return Views::instance()->setSlug('wsklad')->setPluginDir($this->environment()->get('plugin_directory_path'));
	}

	/**
	 * Tools
	 *
	 * @return Tools\Core
	 */
	public function tools(): Tools\Core
	{
		return Tools\Core::instance();
	}

	/**
	 * Logger
	 *
	 * @param string $channel
	 * @param string $name
	 * @param mixed $hard_level
	 *
	 * @return LoggerInterface
	 */
	public function log(string $channel = 'main', string $name = '', $hard_level = null)
	{
		$channel = strtolower($channel);

		if(!isset($this->log[$channel]))
		{
			if('' === $name)
			{
				$name = $channel;
			}

			$path = '';
			$max_files = $this->settings('logs')->get('logger_files_max', 30);

			$logger = new Logger($channel);

			switch($channel)
			{
				case 'tools':
					$path = $this->environment()->get('wsklad_tools_logs_directory') . '/' . $name . '.log';
					$level = $this->settings('logs')->get('logger_tools_level', 'logger_level');
					break;
				case 'accounts':
					$path = $name . '.log';
					$level = $this->settings('logs')->get('logger_accounts_level', 'logger_level');
					break;
				default:
					$level = $this->settings('logs')->get('logger_level', 300);
			}

			if('logger_level' === $level)
			{
				$level = $this->settings('logs')->get('logger_level', 300);
			}

			if(!is_null($hard_level))
			{
				$level = $hard_level;
			}

			if('' === $path)
			{
				$path = $this->environment()->get('wsklad_logs_directory') . '/main.log';
			}

			try
			{
				$uid_processor = new Processor();
				$formatter = new Formatter();
				$handler = new Handler($path, $max_files, $level);

				$handler->setFormatter($formatter);

				$logger->pushProcessor($uid_processor);
				$logger->pushHandler($handler);
			}
			catch(\Throwable $e){}

			/**
			 * Внешние назначения для логгера
			 *
			 * @param LoggerInterface $logger Текущий логгер
			 *
			 * @return LoggerInterface
			 */
			if(has_filter('wsklad_log_load_before'))
			{
				$logger = apply_filters('wsklad_log_load_before', $logger);
			}

			$this->log[$channel] = $logger;
		}

		return $this->log[$channel];
	}

	/**
	 * Settings
	 *
	 * @param string $context
	 *
	 * @return SettingsInterface
	 */
	public function settings(string $context = 'main')
	{
		if(!isset($this->settings[$context]))
		{
			switch($context)
			{
				case 'logs':
					$class = LogsSettings::class;
					break;
				case 'interface':
					$class = InterfaceSettings::class;
					break;
				default:
					$class = MainSettings::class;
			}

			$settings = new $class();

			try
			{
				$settings->init();
			}
			catch(\Throwable $e)
			{
				wsklad()->log()->error($e->getMessage(), ['exception' => $e]);
			}

			$this->settings[$context] = $settings;
		}

		return $this->settings[$context];
	}

	/**
	 * Timer
	 *
	 * @return Timer
	 */
	public function timer(): Timer
    {
		if(is_null($this->timer))
		{
			$timer = new Timer();

			$php_max_execution = $this->environment()->get('php_max_execution_time', 20);

			if($php_max_execution !== $this->settings()->get('php_max_execution_time', $php_max_execution))
			{
				$php_max_execution = $this->settings()->get('php_max_execution_time', $php_max_execution);
			}

			$timer->setMaximum($php_max_execution);

			$this->timer = $timer;
		}

		return $this->timer;
	}

	/**
	 * Load localisation
	 */
	public function localization()
	{
		$locale = determine_locale();

		if(has_filter('plugin_locale'))
		{
			$locale = apply_filters('plugin_locale', $locale, 'wsklad');
		}

		load_textdomain('wsklad', WP_LANG_DIR . '/plugins/wsklad-' . $locale . '.mo');
		load_textdomain('wsklad', wsklad()->environment()->get('plugin_directory_path') . 'assets/languages/wsklad-' . $locale . '.mo');

		wsklad()->log()->debug(__('Localization loaded.', 'wsklad'), ['locale' => $locale]);
	}

	/**
	 * Use in plugin for DB queries
	 *
	 * @return wpdb
	 */
	public function database(): wpdb
	{
		global $wpdb;
		return $wpdb;
	}

	/**
	 * Main instance of Admin
	 *
	 * @return Admin
	 */
	public function admin(): Admin
	{
		ob_start();
		return Admin::instance();
	}

	/**
	 * Get data if set, otherwise return a default value or null
	 * Prevents notices when data is not set
	 *
	 * @param mixed $var variable
	 * @param string $default default value
	 *
	 * @return mixed
	 */
	public function getVar(&$var, $default = null)
	{
		return $var ?? $default;
	}

	/**
	 * Define constant if not already set
	 *
	 * @param string $name constant name
	 * @param string|bool $value constant value
	 */
	public function define(string $name, $value)
	{
		if(!defined($name))
		{
			define($name, $value);
		}
	}
}