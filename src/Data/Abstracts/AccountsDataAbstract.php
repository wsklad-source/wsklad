<?php namespace Wsklad\Data\Abstracts;

defined('ABSPATH') || exit;

use Wsklad\Log\Logger;
use function Wsklad\core;

/**
 * AccountsDataAbstract
 *
 * @package Wsklad\Data\Abstracts
 */
abstract class AccountsDataAbstract extends WithMetaDataAbstract
{
	/**
	 * This is the name of this object type
	 *
	 * @var string
	 */
	protected $object_type = 'account';

	/**
	 * Logger
	 *
	 * @param string $channel
	 *
	 * @return Logger
	 */
	public function log(string $channel = 'accounts'): Logger
	{
		$name = $this->getUploadDirectory('logs') . DIRECTORY_SEPARATOR . $channel;

		if($channel === 'accounts')
		{
			$name = $this->getUploadDirectory('logs') . DIRECTORY_SEPARATOR . 'main';
		}

		$hard_level = $this->getOptionsByKey('logger_level', 'logger_level');

		if('logger_level' === $hard_level)
		{
			$hard_level = null;
		}

		return core()->log($channel, $name, $hard_level);
	}

	/**
	 * Get options by key
	 *
	 * @param string $key - unique option id
	 * @param null $default - false for error
	 *
	 * @return mixed
	 */
	public function getOptionsByKey(string $key = '', $default = null)
	{
		$all_options = $this->getOptions();

		if($key !== '')
		{
			if(array_key_exists($key, $all_options))
			{
				return $all_options[$key];
			}

			if(false === is_null($default))
			{
				return $default;
			}

			return false;
		}

		return $all_options;
	}
}