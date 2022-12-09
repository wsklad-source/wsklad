<?php namespace Wsklad\Admin\Accounts;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Exception;
use Wsklad\Account;
/**
 * Class Verification
 *
 * @package Wsklad\Admin\Accounts
 */
class Verification
{
	use SingletonTrait;

	/**
	 * @var Account
	 */
	protected $account;

	/**
	 * Verification constructor.
	 */
	public function __construct()
	{
		$account_id = wsklad()->getVar($_GET['account_id'], 0);
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
			add_action('wsklad_admin_show', [$this, 'output_error'], 10);
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
				'dismissible' => true,
				'type' => 'error',
				'data' => __('The account from Moy Sklad has been deleted. It is not possible to check the relevance.', 'wsklad')
			];
		}
		else
		{
			$notice_args =
			[
				'dismissible' => true,
				'type' => 'update',
				'data' => sprintf
				(
					'%1$s <span class="name">%2$s</span>',
					__('The following accounts have been successfully verified and connected:', 'wsklad'),
					$account->get_name()
				)
			];

			try
			{
				$response = $account->moysklad()->entity()->employee()->getList();
				$response = json_decode($response, true);

				if(!isset($response['meta']))
				{
					$notice_args =
					[
						'dismissible' => true,
						'type' => 'error',
						'data' => __('Account connection error. Test connection is not success.', 'wsklad')
					];
				}
			}
			catch(Exception $e)
			{
				$notice_args['type'] = 'error';
				$notice_args['data'] = sprintf
				(
					'%1$s <span class="name">%2$s</span>',
					__('The following accounts contain errors and have been disabled:', 'wsklad'),
					$account->get_name()
				);
				$notice_args['extra_data'] = $e->getMessage();
			}
		}

		wsklad()->admin()->notices()->create($notice_args);
		wp_safe_redirect(wsklad_admin_accounts_get_url());
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
		wsklad()->views()->getView('accounts/error.php');
	}
}