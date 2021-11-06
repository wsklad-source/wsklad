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
 * Class ConnectionSettings
 *
 * @package Wsklad\Settings
 */
class ConnectionSettings extends SettingsAbstract
{
	/**
	 * ConnectionSettings constructor.
	 */
	public function __construct()
	{
		$this->setOptionName('connection');
	}

	/**
	 * Account connected?
	 *
	 * @return bool
	 */
	public function isConnected()
	{
		if($this->get('login', '') !== '')
		{
			return true;// todo: проверять подключение раз в n времени с сохранением в транзиты
		}

		return false;
	}
}