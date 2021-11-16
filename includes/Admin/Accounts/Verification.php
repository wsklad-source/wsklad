<?php
/**
 * Namespace
 */
namespace Wsklad\Admin\Accounts;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Exception;
use Wsklad\Account;
use Wsklad\Traits\Singleton;

/**
 * Class Verification
 *
 * @package Wsklad\Admin\Accounts
 */
class Verification
{
	use Singleton;

	/**
	 * @var Account
	 */
	protected $account;

	/**
	 * Verification constructor.
	 */
	public function __construct()
	{
		$account_id = wsklad_get_var($_GET['account_id'], 0);
		$error = false;

		try
		{
			$account = new Account($account_id);

			if(!$account->get_storage()->is_existing_by_id($account_id))
			{
				$error = true;
			}

			$this->setAccount($account);
		}
		catch(Exception $e)
		{
			$error = true;
		}

		if($error)
		{
			add_action(WSKLAD_ADMIN_PREFIX . 'show', [$this, 'output_error'], 10);
		}
		else
		{
			$this->process($this->getAccount());
		}
	}

	/**
	 * Verification processing
	 *
	 * @param $account
	 */
	public function process($account)
	{
		$account_status = $account->get_status();

		if($account_status === 'deleted')
		{
			$notice_args =
			[
				'type' => 'error',
				'data' => __('The account from Moy Sklad has been deleted. It is not possible to check the relevance.', 'wsklad')
			];
		}
		else
		{
			$notice_args =
			[
				'type' => 'update',
				'data' => sprintf
				(
					'%1$s <span class="name">%2$s</span>',
					__('The following accounts have been successfully verified and connected:', 'wsklad'),
					$account->get_name()
				)
			];

			if(!$account->moysklad())
			{
				$notice_args['type'] = 'error';
				$notice_args['data'] = sprintf
				(
					'%1$s <span class="name">%2$s</span>',
					__('The following accounts contain errors and have been disabled:', 'wsklad'),
					$account->get_name()
				);
			}
		}

		wsklad_admin()->notices()->create($notice_args);
		wp_safe_redirect(wsklad_admin_accounts_get_url('list'));
		die;
	}

	/**
	 * @return Account
	 */
	public function getAccount()
	{
		return $this->account;
	}

	/**
	 * @param Account $account
	 */
	public function setAccount($account)
	{
		$this->account = $account;
	}

	/**
	 * Output error
	 */
	public function output_error()
	{
		wsklad_get_template('accounts/error.php');
	}
}