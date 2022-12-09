<?php namespace Wsklad\Admin\Accounts;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Exception;
use Wsklad\Account;

/**
 * Class Delete
 *
 * @package Wsklad\Admin\Accounts
 */
class Delete
{
	use SingletonTrait;

	/**
	 * @var Account
	 */
	protected $account;

	/**
	 * Delete constructor.
	 * @throws Exception
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
	 * Delete processing
	 *
	 * @param $account
	 *
	 * @throws Exception
	 */
	public function process($account)
	{
		$delete = false;
		$redirect = true;
		$force_delete = false;
		$account_status = $account->get_status();

		$notice_args['type'] = 'error';
		$notice_args['data'] = __('Error. The account to be deleted is active and cannot be deleted.', 'wsklad');

		/**
		 * Защита от удаления активных соединений
		 */
		if(!$account->is_status('active') && !$account->is_status('processing'))
		{
			/**
			 * Окончательное удаление черновиков без корзины
			 */
			if($account_status === 'draft' && 'yes' === wsklad()->settings()->get('accounts_draft_delete', 'yes'))
			{
				$delete = true;
				$force_delete = true;
			}

			/**
			 * Помещение в корзину без удаления
			 */
			if($account_status !== 'deleted' && $force_delete === false)
			{
				$delete = true;
			}

			/**
			 * Окончательное удаление из корзины - вывод формы для подтверждения удаления
			 */
			if($account_status === 'deleted')
			{
				$redirect = false;
				$delete_form = new DeleteForm();

				if(!$delete_form->save())
				{
					add_action('wsklad_admin_accounts_form_delete_show', [$delete_form, 'output_form']);
					add_action('wsklad_admin_show', [$this, 'output'], 10);
				}
				else
				{
					$delete = true;
					$force_delete = true;
					$redirect = true;
				}
			}

			/**
			 * Удаление с переносом в список всех учетных записей и выводом уведомления об удалении
			 */
			if($delete)
			{
				$notice_args =
				[
					'type' => 'update',
					'data' => __('The account has been marked as deleted.', 'wsklad')
				];

				if($force_delete)
				{
					$notice_args =
					[
						'type' => 'update',
						'data' => __('The account has been successfully disconnected.', 'wsklad')
					];
				}

				if(!$account->delete($force_delete))
				{
					$notice_args['type'] = 'error';
					$notice_args['data'] = __('Deleting error. Please retry again.', 'wsklad');
				}
			}
		}

		if($redirect)
		{
			wsklad()->admin()->notices()->create($notice_args);
			wp_safe_redirect(wsklad_admin_accounts_get_url());
			die;
		}
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
		wsklad()->views()->getView('accounts/delete_error.php');
	}

	/**
	 * Output permanent remove
	 *
	 * @return void
	 */
	public function output()
	{
		wsklad()->views()->getView('accounts/delete.php');
	}
}