<?php namespace Wsklad\Admin\Accounts;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Wsklad\Data\Entities\Account;
use Wsklad\Traits\UtilityTrait;

/**
 * Class Verification
 *
 * @package Wsklad\Admin\Accounts
 */
class Verification
{
	use SingletonTrait;
	use UtilityTrait;

	/**
	 * @var Account
	 */
	protected $account;

	/**
	 * Verification constructor.
	 */
	public function __construct()
	{
        $error = false;
        $account_id = 0;

        if(!empty($_GET['account_id']))
        {
            $account_id = sanitize_text_field(wp_unslash($_GET['account_id']));
        }

        /**
         * A bare GET link is enough to make an administrator's browser talk to Moy Sklad
         * and get here, so the request must prove it was rendered by the accounts list.
         * The check runs before anything else so a forged request changes no state.
         */
        $nonce = '';
        if(!empty($_GET['_wpnonce']))
        {
            $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));
        }

        if(false === wp_verify_nonce($nonce, 'wsklad_accounts_verify'))
        {
            wsklad()->admin()->notices()->create
            (
                [
                    'dismissible' => true,
                    'type' => 'error',
                    'data' => esc_html__('The account could not be verified: the request has expired or came from an external source. Use the Verification link in the accounts list.', 'wsklad')
                ]
            );

            wsklad()->log()->notice
            (
                'Account verification request rejected: invalid nonce.',
                ['account_id' => $account_id]
            );

            return;
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
	 * Verification processing
	 *
	 * @param $account
	 */
	public function process($account)
	{
		$account_status = $account->getStatus();

		if($account_status === 'deleted')
		{
			$notice_args =
			[
				'dismissible' => true,
				'type' => 'error',
				'data' => esc_html__('The account from Moy Sklad has been deleted. It is not possible to check the relevance.', 'wsklad')
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
					esc_html__('The following accounts have been successfully verified and connected:', 'wsklad'),
                    esc_html($account->getName())
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
						'data' => esc_html__('Account connection error. Test connection is not success.', 'wsklad')
					];
				}
			}
		catch(\Throwable $e)
		{
			$notice_args['type'] = 'error';
			$notice_args['data'] = sprintf
			(
				'%1$s <span class="name">%2$s</span>',
                    esc_html__('The following account could not be verified because of a connection error:', 'wsklad'),
				esc_html($account->getName())
			);
			$notice_args['extra_data'] = $e->getMessage();
		}
		}

		wsklad()->admin()->notices()->create($notice_args);

		$this->utilityRedirect($this->utilityAdminAccountsGetUrl('all'));
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
		wsklad()->views()->getView('accounts/error.php');
	}
}