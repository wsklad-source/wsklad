<?php namespace Wsklad;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Wsklad\Exceptions\Exception;
use Wsklad\Exceptions\RuntimeException;

/**
 * Environment
 *
 * @package Wsklad
 */
final class Environment
{
	use SingletonTrait;

	/**
	 * @var array Environ data
	 */
	private $data;

	/**
	 * Environment constructor
	 */
	public function __construct(){}

	/**
	 * Get data
	 *
	 * @param $key
	 * @param $default
	 *
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		if(isset($this->data[$key]))
		{
			return $this->data[$key];
		}

		$key_getter = str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));

		$getter = "init$key_getter";

		if(is_callable([$this, $getter]))
		{
			try
			{
				$getter_value = $this->{$getter}($default);
				$this->set($key, $getter_value);
			}
			catch(Exception $e){}

			return $this->get($key);
		}

		if(false === is_null($default))
		{
			return $default;
		}

		return false;
	}

	/**
	 * Set environ data
	 *
	 * @param $key
	 * @param $value
	 */
	public function set($key, $value)
	{
		$this->data[$key] = $value;
	}

	/**
	 * WordPress upload directory
	 *
	 * @return bool
	 * @throws RuntimeException
	 */
	public function initUploadDirectory()
	{
		if(false === function_exists('wp_upload_dir'))
		{
			throw new RuntimeException('function wp_upload_dir is not exists');
		}

		$wp_upload_dir = wp_upload_dir();

		$this->set('upload_directory', $wp_upload_dir['basedir']);

		return $this->get('upload_directory');
	}

	/**
	 * WordPress plugin directory URL
	 *
	 * @return string
	 */
	public function initPluginDirectoryUrl()
	{
		if(false === function_exists('plugin_dir_url'))
		{
			throw new RuntimeException('Function plugin_dir_url is not exists.');
		}

		$this->set('plugin_directory_url', plugin_dir_url(WSKLAD_PLUGIN_FILE));

		return $this->get('plugin_directory_url');
	}

	/**
	 * WordPress plugin directory path
	 *
	 * @return string
	 */
	public function initPluginDirectoryPath()
	{
		if(false === function_exists('plugin_dir_path'))
		{
			throw new RuntimeException('Function plugin_dir_path is not exists.');
		}

		$this->set('plugin_directory_path', plugin_dir_path(WSKLAD_PLUGIN_FILE));

		return $this->get('plugin_directory_path');
	}

	/**
	 * WordPress plugin basename
	 *
	 * @return string
	 */
	public function initPluginBasename()
	{
		if(false === function_exists('plugin_basename'))
		{
			throw new RuntimeException('Function plugin_basename is not exists.');
		}

		$this->set('plugin_basename', plugin_basename(WSKLAD_PLUGIN_FILE));

		return $this->get('plugin_basename');
	}

	/**
	 * PHP post max size
	 */
	public function initPhpPostMaxSize()
	{
		$this->set('php_post_max_size', ini_get('post_max_size'));

		return $this->get('php_post_max_size');
	}

	/**
	 * PHP max execution time
	 */
	public function initPhpMaxExecutionTime()
	{
		$this->set('php_max_execution_time', ini_get('max_execution_time'));

		return $this->get('php_max_execution_time');
	}

	/**
	 * WSKLAD upload directory
	 *
	 * @return bool
	 */
	public function initWskladUploadDirectory()
	{
		$wsklad_upload_dir = $this->get('upload_directory') . DIRECTORY_SEPARATOR . 'wsklad';

		$this->set('wsklad_upload_directory', $wsklad_upload_dir);

		return $this->get('wsklad_upload_directory');
	}

	/**
	 * WSKLAD logs directory
	 *
	 * @return bool
	 */
	public function initWskladLogsDirectory()
	{
		$wsklad_logs_dir = $this->get('wsklad_upload_directory') . DIRECTORY_SEPARATOR . 'logs';

		$this->set('wsklad_logs_directory', $wsklad_logs_dir);

		return $this->get('wsklad_logs_directory');
	}

	/**
	 * WSKLAD tools directory
	 *
	 * @return bool
	 */
	public function initWskladToolsDirectory()
	{
		$wsklad_logs_dir = $this->get('wsklad_upload_directory') . DIRECTORY_SEPARATOR . 'tools';

		$this->set('wsklad_tools_directory', $wsklad_logs_dir);

		return $this->get('wsklad_tools_directory');
	}

	/**
	 * WSKLAD tools logs directory
	 *
	 * @return bool
	 */
	public function initWskladToolsLogsDirectory()
	{
		$wsklad_logs_dir = $this->get('wsklad_tools_directory') . DIRECTORY_SEPARATOR . 'logs';

		$this->set('wsklad_tools_logs_directory', $wsklad_logs_dir);

		return $this->get('wsklad_tools_logs_directory');
	}

	/**
	 * WSKLAD accounts directory
	 *
	 * @return bool
	 */
	public function initWskladAccountsDirectory()
	{
		$wsklad_logs_dir = $this->get('wsklad_upload_directory') . DIRECTORY_SEPARATOR . 'accounts';

		$this->set('wsklad_accounts_directory', $wsklad_logs_dir);

		return $this->get('wsklad_accounts_directory');
	}

	/**
	 * WSKLAD accounts logs directory
	 *
	 * @return bool
	 */
	public function initWskladAccountsLogsDirectory()
	{
		$wsklad_logs_dir = $this->get('wsklad_accounts_directory') . DIRECTORY_SEPARATOR . 'logs';

		$this->set('wsklad_accounts_logs_directory', $wsklad_logs_dir);

		return $this->get('wsklad_accounts_logs_directory');
	}

	/**
	 * WSKLAD version
	 *
	 * @return bool
	 */
	public function initWskladVersion()
	{
		if(!function_exists('get_file_data'))
		{
			throw new RuntimeException('Function get_file_data is not exists');
		}

		$plugin_data = get_file_data(WSKLAD_PLUGIN_FILE, ['Version' => 'Version']);

		$this->set('wsklad_version', $plugin_data['Version']);

		return $this->get('wsklad_version');
	}

	/**
	 * Get all data
	 *
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}

	/**
	 * Set all data
	 *
	 * @param array $data
	 */
	public function setData(array $data)
	{
		$this->data = $data;
	}
}