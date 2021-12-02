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
use RuntimeException;
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
final class Core
{
	use Singleton;
	use LoggerAwareTrait;

	/**
	 * @var MainSettings Settings
	 */
	private $settings;

	/**
	 * @var array All loaded extensions
	 */
	private $extensions = [];

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

		try
		{
			$this->loadExtensions();
		}
		catch(Exception $e){}

		try
		{
			$this->initExtensions();
		}
		catch(Exception $e){}

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
	 * @throws RuntimeException
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
				throw new RuntimeException('load_settings: exception - ' . $e->getMessage());
			}

			$this->settings = $settings;
		}

		return $this->settings;
	}

	/**
	 * Initializing extensions
	 * If an extension ID is specified, only the specified extension is loaded
	 *
	 * @param string $extension_id
	 *
	 * @return boolean
	 * @throws RuntimeException
	 */
	public function initExtensions($extension_id = '')
	{
		try
		{
			$extensions = $this->getExtensions();
		}
		catch(Exception $e)
		{
			throw new RuntimeException('init_extensions: $extensions - ' . $e->getMessage());
		}

		if(!is_array($extensions))
		{
			throw new RuntimeException('init_extensions: $extensions is not array');
		}

		/**
		 * Init specified extension
		 */
		if('' !== $extension_id)
		{
			if(!array_key_exists($extension_id, $extensions))
			{
				throw new RuntimeException('init_extensions: extension not found by id');
			}

			$init_extension = $extensions[$extension_id];

			if(!is_object($init_extension))
			{
				throw new RuntimeException('init_extensions: $extensions[$extension_id] is not object');
			}

			if($init_extension->isInitialized())
			{
				throw new RuntimeException('init_extensions: old initialized');
			}

			if(!method_exists($init_extension, 'init'))
			{
				throw new RuntimeException('init_extensions: method init not found');
			}

			try
			{
				$init_extension->init();
			}
			catch(Exception $e)
			{
				throw new RuntimeException('init_extensions: exception by extension - ' . $e->getMessage());
			}

			$init_extension->setInitialized(true);

			return true;
		}

		/**
		 * Init all extensions
		 */
		foreach($extensions as $extension => $extension_object)
		{
			try
			{
				$this->initExtensions($extension);
			}
			catch(Exception $e)
			{
				continue;
			}
		}

		return true;
	}

	/**
	 * Extensions loading
	 *
	 * @return void
	 *
	 * @throws RuntimeException|Exception
	 */
	private function loadExtensions()
	{
		$extensions = [];

		if('yes' === $this->settings()->get('extensions', 'yes'))
		{
			$extensions = apply_filters(WSKLAD_PREFIX . 'extensions_loading', $extensions);
		}

		try
		{
			$this->setExtensions($extensions);
		}
		catch(Exception $e)
		{
			throw new RuntimeException('load_extensions: set_extensions - ' . $e->getMessage());
		}
	}

	/**
	 * Get initialized extensions
	 *
	 * @param string $extension_id
	 *
	 * @return array|object
	 *
	 * @throws RuntimeException
	 */
	public function getExtensions($extension_id = '')
	{
		if('' !== $extension_id)
		{
			if(array_key_exists($extension_id, $this->extensions))
			{
				return $this->extensions[$extension_id];
			}

			throw new RuntimeException('get_extensions: $extension_id is unavailable');
		}

		return $this->extensions;
	}

	/**
	 * @param array $extensions
	 *
	 * @return bool
	 *
	 * @throws RuntimeException
	 */
	public function setExtensions($extensions)
	{
		if(is_array($extensions))
		{
			$this->extensions = $extensions;
			return true;
		}

		throw new RuntimeException('set_extensions: $extensions is not valid');
	}
}