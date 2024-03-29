<?php namespace Wsklad\Traits;

defined('ABSPATH') || exit;

/**
 * UtilityTrait
 *
 * @package Wsklad\Traits
 */
trait UtilityTrait
{
	/**
	 * Convert kb, mb, gb to bytes
	 *
	 * @param $size
	 *
	 * @return float|int
	 */
	public function utilityConvertFileSize($size)
	{
		if(empty($size))
		{
			return 0;
		}

		$type = $size[strlen($size) - 1];

		if(!is_numeric($type))
		{
			$size = (int) $size;

			switch($type)
			{
				case 'K':
					$size *= 1024;
					break;
				case 'M':
					$size *= 1024 * 1024;
					break;
				case 'G':
					$size *= 1024 * 1024 * 1024;
					break;
				default:
					return $size;
			}
		}

		return (int)$size;
	}

	/**
	 * Is WSKLAD admin tools request?
	 *
	 * @param string $tool_id
	 *
	 * @return bool
	 */
	public function utilityIsWskladAdminToolsRequest($tool_id = '')
	{
		if('' === $tool_id)
		{
			return true;
		}

		$get_tool_id = wsklad()->getVar($_GET['tool_id'], '');

		if($get_tool_id !== $tool_id)
		{
			return false;
		}

		return true;
	}

	/**
	 * Is WSKLAD admin request?
	 *
	 * @return bool
	 */
	public function utilityIsWskladAdmin()
	{
		if(false !== is_admin() && 'wsklad' === wsklad()->getVar($_GET['page'], ''))
		{
			return true;
		}

		return false;
	}

	/**
	 * Is WSKLAD admin section request?
	 *
	 * @param string $section
	 *
	 * @return bool
	 */
	public function utilityIsWskladAdminSectionRequest($section = '')
	{
		if(wsklad()->getVar($_GET['section'], '') !== $section)
		{
			return false;
		}

		if($this->utilityIsWskladAdmin())
		{
			return true;
		}

		return false;
	}

	/**
	 * @param string $tool_id
	 *
	 * @return string
	 */
	public function utilityAdminToolsGetUrl($tool_id = '')
	{
		$path = 'admin.php?page=wsklad_tools&section=main';

		if('' === $tool_id)
		{
			return admin_url($path);
		}

		$path = 'admin.php?page=wsklad_tools&section=main&tool_id=' . $tool_id;

		return admin_url($path);
	}

	/**
	 * @param string $action
	 * @param string $account_id
	 *
	 * @return string
	 */
	public function utilityAdminAccountsGetUrl($action = 'all', $account_id = '')
	{
		$path = 'admin.php?page=wsklad';

		if('all' !== $action)
		{
			$path .= '&do_action=' . $action;
		}

		if('' === $account_id)
		{
			return admin_url($path);
		}

		$path .= '&account_id=' . $account_id;

		return admin_url($path);
	}

	/**
	 * @param $data
	 * @param bool $die
	 *
	 * @return void
	 */
	public function dump($data, bool $die = false)
	{
		echo '<pre>';
		var_dump($data);
		echo '</pre>';

		if($die)
		{
			die;
		}
	}
}