<?php namespace Wsklad\Admin\Accounts;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Exception;
use Wsklad\Data\Entities\Account;
use Wsklad\Traits\UtilityTrait;

/**
 * Class Delete
 *
 * @package Wsklad\Admin\Accounts
 */
class Delete
{
	use SingletonTrait;
	use UtilityTrait;

	/**
	 * @var Account
	 */
	protected $account;

	/**
	 * Delete constructor.
     *
	 * @throws Exception
	 */
	public function __construct()
	{
        $error = false;
        $account_id = 0;

        if(!empty($_GET['account_id']))
        {
            $account_id = sanitize_text_field(wp_unslash($_GET['account_id']));
        }

		try
		{
			$account = new Account($account_id);

			if(!$account->getStorage()->isExistingById($account_id))
			{
				$error = true;
			}

			$this->setAccount($account);
		}
		catch(\Throwable $e)
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
		$account_status = $account->getStatus();

		/**
		 * A GET that changes state must carry a nonce.
		 *
		 * Without one, any page on the internet could put an `<img src>` in front of a
		 * logged-in shop manager and silently move their accounts to the trash. The
		 * nonce is verified before anything is read from the account, and the check is
		 * placed so that the confirmation form branch (a `deleted` account) is reached
		 * only after it passes — otherwise the form would be shown instead of processed.
		 */
		if(!$this->verifyNonce())
		{
			wsklad()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => __('The request to disconnect the account could not be verified. Open the accounts list and try again.', 'wsklad')
				]
			);

			$this->utilityRedirect($this->utilityAdminAccountsGetUrl());

			return;
		}

		$notice_args['type'] = 'error';
		$notice_args['data'] = __('Error. This account is active, so it cannot be deleted. Disconnect it first.', 'wsklad');

		/**
		 * Active and processing connections are protected from deletion.
		 */
		if(!$account->isStatus('active') && !$account->isStatus('processing'))
		{
			/**
			 * Drafts are removed for good, with no trash step, when the setting says so.
			 */
			if($account_status === 'draft' && 'yes' === wsklad()->settings()->get('accounts_draft_delete', 'yes'))
			{
				$delete = true;
				$force_delete = true;
			}

			/**
			 * Anything else goes to the trash first.
			 */
			if($account_status !== 'deleted' && $force_delete === false)
			{
				$delete = true;
			}

			/**
			 * An account already in the trash shows the confirmation form; only the
			 * form POST removes the row.
			 */
			if($account_status === 'deleted')
			{
				$redirect = false;
				$delete_form = new DeleteForm();

				if(!$delete_form->save())
				{
					add_action('wsklad_admin_accounts_form_delete_show', [$delete_form, 'outputForm']);
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
			 * Deleting shows a notice and returns to the accounts list.
			 */
			if($delete)
			{
				$notice_args =
				[
					'type' => 'update',
					'data' => __('The account has been moved to the trash.', 'wsklad')
				];

				if($force_delete)
				{
					$notice_args =
					[
						'type' => 'update',
						'data' => __('The account has been disconnected.', 'wsklad')
					];
				}

				if(!$account->delete($force_delete))
				{
					$notice_args['type'] = 'error';
					$notice_args['data'] = __('Could not delete the account. Please try again.', 'wsklad');
				}
			}
		}

		if($redirect)
		{
			wsklad()->admin()->notices()->create($notice_args);
			$this->utilityRedirect($this->utilityAdminAccountsGetUrl());
		}
	}

	/**
	 * Verify the CSRF nonce of the disconnect request.
	 *
	 * @return bool
	 */
	private function verifyNonce(): bool
	{
		$nonce = '';

		if(!empty($_GET['_wpnonce']))
		{
			$nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));
		}

		return (bool) wp_verify_nonce($nonce, 'wsklad_accounts_delete');
	}

	/**
	 * @return Account
	 */
	public function getAccount(): Account
    {
		return $this->account;
	}

	/**
	 * @param Account $account
	 */
	public function setAccount(Account $account)
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
        $args['account'] = $this->getAccount();

		wsklad()->views()->getView('accounts/delete.php', $args);
	}
}