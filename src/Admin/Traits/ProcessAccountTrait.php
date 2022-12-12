<?php namespace Wsklad\Admin\Traits;

defined('ABSPATH') || exit;

use Wsklad\Account;
use Wsklad\Exceptions\Exception;

/**
 * ProcessConfigurationTrait
 *
 * @package Wsklad\Admin\Traits
 */
trait ProcessAccountTrait
{
	/**
	 * @var Account
	 */
	protected $account;

	/**
	 * @param $account_id
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function setAccount($account_id): bool
	{
		if($account_id instanceof Account)
		{
			$this->account = $account_id;

			return false;
		}

		$error = false;

		try
		{
			$account = new Account($account_id);

			if(!$account->get_storage()->is_existing_by_id($account_id))
			{
				$error = true;
			}

			$this->account = $account;
		}
		catch(Exception $e)
		{
			$error = true;
		}

		return $error;
	}

	/**
	 * @return Account
	 */
	public function getAccount(): Account
	{
		return $this->account;
	}
}