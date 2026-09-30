<?php namespace Wsklad\Admin\Accounts;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Wsklad\Admin\Traits\ProcessAccountTrait;
use Wsklad\Traits\DatetimeUtilityTrait;
use Wsklad\Traits\SectionsTrait;
use Wsklad\Traits\UtilityTrait;

/**
 * MainUpdate
 *
 * @package Wsklad\Admin
 */
class MainUpdate
{
	use SingletonTrait;
	use DatetimeUtilityTrait;
	use UtilityTrait;
	use SectionsTrait;
	use ProcessAccountTrait;

	/**
	 * Update processing
	 */
	public function process()
	{
		$account = $this->getAccount();

		add_filter('wsklad_accounts-update_form_load_fields', [$this, 'accountsFieldsOther'], 120, 1);
		add_filter('wsklad_accounts-update_form_load_fields', [$this, 'accountsFieldsLogs'], 100, 1);

		if($account->getConnectionType() === 'token')
		{
			add_filter('wsklad_accounts-update_form_load_fields', [$this, 'accountsFieldsToken'], 20, 1);
		}
		else
		{
			add_filter('wsklad_accounts-update_form_load_fields', [$this, 'accountsFieldsLoginAndPassword'], 20, 1);
		}

		$form = new UpdateForm();

		$form_data = $account->getOptions();

        $form_data['status'] = $account->isEnabled() ? 'yes' : 'no';
		$form_data['moysklad_login'] = $account->getMoyskladLogin();

		/**
		 * ⚠ The password and the token are deliberately NOT pre-filled.
		 *
		 * They used to be, which put the live Moy Sklad credential into the HTML source
		 * of the account edit screen. A password field hides the value visually and does
		 * nothing about the DOM: view-source, a browser extension, a screen share, or a
		 * proxy log all reveal it. Leaving the fields empty and saving unchanged is the
		 * normal path, so nothing is lost — and the value never leaves the database.
		 */
		$form_data['moysklad_password'] = '';
		$form_data['moysklad_token'] = '';

		$form->loadSavedData($form_data);

		if(isset($_GET['form']) && $_GET['form'] === $form->getId())
		{
			$data = $form->save();

			if($data)
			{
				/**
				 * An empty secret field means "keep the stored one", not "clear it".
				 *
				 * Without this, saving the form for an unrelated reason — changing the log
				 * level, say — would silently destroy the credential and break the account.
				 */
				if('' === trim((string) $data['moysklad_password']))
				{
					$data['moysklad_password'] = $account->getMoyskladPassword('edit');
				}

				if('' === trim((string) $data['moysklad_token']))
				{
					$data['moysklad_token'] = $account->getMoyskladToken('edit');
				}

                // The enabled checkbox is checked.
                if($data['status'] === 'yes')
                {
                    if($account->isEnabled() === false)
                    {
                        $account->setStatus('active');
                    }
                }
                // The enabled checkbox is not checked.
                else
                {
                    $account->setStatus('inactive');
                }

				$account->setMoyskladPassword($data['moysklad_password']);
				$account->setMoyskladToken($data['moysklad_token']);

				unset($data['status'], $data['moysklad_login'], $data['moysklad_password'], $data['moysklad_token']);

				$account->setDateModify(time());
				$account->setOptions($data);

				$saved = $account->save();

				if($saved)
				{
					$info_message = __('Account update success.', 'wsklad');

					$account->log()->info($info_message);

					wsklad()->admin()->notices()->create
					(
						[
							'type' => 'update',
							'data' => $info_message
						]
					);
				}
				else
				{
					$error_message = __('Account update error. Please retry saving or change fields.', 'wsklad');

					$account->log()->error($error_message);

					wsklad()->admin()->notices()->create
					(
						[
							'type' => 'error',
							'data' => $error_message
						]
					);
				}
			}
		}

		add_action('wsklad_admin_accounts_sections_single_show', [$form, 'outputForm'], 10);
        add_action('wsklad_admin_accounts_update_sidebar_show', [$this, 'outputSidebar'], 10);
	}

	/**
	 * Accounts fields: token
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function accountsFieldsToken(array $fields): array
	{
        $fields['title_auth'] =
        [
            'title' => __('Authorization data', 'wsklad'),
            'type' => 'title',
            'description' => sprintf
            (
                '%s %s',
                __('Authorization of requests for current account.', 'wsklad'),
                __('Used for authorization in Moy Sklad service.', 'wsklad')
            )
        ];

		$fields['moysklad_token'] =
		[
			'title' => __('Token', 'wsklad'),
			'type' => 'password',
			/**
			 * ⚠ The revocation warning is on the field the user reads *before* going to
			 * Moy Sklad, not in a notice after the fact. W-031 asked for it before the
			 * exchange, and this is the only place on the screen that is read before.
			 */
			'description' => sprintf
			(
				'%s<br /><b>%s</b> %s<br />%s <a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
				__('Get it in account on the Moy Sklad. Leave the field empty to keep the stored token.', 'wsklad'),
				__('Warning:', 'wsklad'),
				__('issuing a new token in Moy Sklad immediately invalidates the previous one. Any account still using the old token — here, on another site, or in another plugin — stops working at that moment.', 'wsklad'),
				__('Issue or revoke tokens at any time:', 'wsklad'),
				esc_url('https://online.moysklad.ru/app/settings/integrations/tokens'),
				__('Open Moy Sklad token settings', 'wsklad')
			),
			'default' => '',
			'css' => 'min-width: 350px;',
		];

		return $fields;
	}

	/**
	 * Accounts fields: login & password
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function accountsFieldsLoginAndPassword(array $fields): array
	{
		$fields['title_auth'] =
		[
			'title' => __('Authorization data', 'wsklad'),
			'type' => 'title',
			'description' => sprintf
            (
                '%s %s',
                __('Authorization of requests for current account.', 'wsklad'),
                __('Used for authorization in Moy Sklad service.', 'wsklad')
            )
		];

		$fields['moysklad_login'] =
		[
			'title' => __('Username', 'wsklad'),
			'type' => 'text',
			'description' => __('Login in Moy Sklad. After adding an account, changing the login is not possible.', 'wsklad'),
			'default' => '',
			'css' => 'min-width: 350px;',
			'class' => 'disabled',
			'disabled' => true
		];

		$fields['moysklad_password'] =
		[
			'title' => __('User password', 'wsklad'),
			'type' => 'password',
			'description' => __('Password for the specified user Moy Sklad. Leave the field empty to keep the stored password.', 'wsklad'),
			'default' => '',
			'css' => 'min-width: 350px;'
		];

		return $fields;
	}

	/**
	 * Accounts fields: logs
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function accountsFieldsLogs($fields): array
	{
		$fields['title_logger'] =
		[
			'title' => __('Event logs', 'wsklad'),
			'type' => 'title',
			'description' => __('Maintaining event logs for the current account. You can view the logs through the extension or via FTP.', 'wsklad'),
		];

		$fields['logger_level'] =
		[
			'title' => __('Level for events', 'wsklad'),
			'type' => 'select',
			'description' => __('All events of the selected level will be recorded in the log file. The higher the level, the less data is recorded.', 'wsklad'),
			'default' => '300',
			'options' =>
				[
					'logger_level' => __('Use level for main events', 'wsklad'),
					'100' => __('DEBUG (100)', 'wsklad'),
					'200' => __('INFO (200)', 'wsklad'),
					'250' => __('NOTICE (250)', 'wsklad'),
					'300' => __('WARNING (300)', 'wsklad'),
					'400' => __('ERROR (400)', 'wsklad'),
				],
		];

		$fields['logger_files_max'] =
		[
			'title' => __('Maximum files', 'wsklad'),
			'type' => 'text',
			'description' => __('Log files created daily. This option on the maximum number of stored files. By default saved of the logs are for the last 30 days.', 'wsklad'),
			'default' => 10,
			'css' => 'min-width: 20px;',
		];

		return $fields;
	}

	/**
	 * Account fields: other
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function accountsFieldsOther($fields): array
	{
		$fields['title_other'] =
		[
			'title' => __('Other parameters', 'wsklad'),
			'type' => 'title',
			'description' => __('Change of data processing behavior for environment compatibility and so on.', 'wsklad'),
		];

		$fields['php_post_max_size'] =
		[
			'title' => __('Maximum size of accepted requests', 'wsklad'),
			'type' => 'text',
			'description' => sprintf
			(
				'%s<br />%s <b>%s</b><br />%s',
				__('Enter the maximum size of accepted requests from Moy Sklad at a time in bytes. May be specified with a dimension suffix, such as 7M, where M = megabyte, K = kilobyte, G - gigabyte.', 'wsklad'),
				__('Current WSKLAD limit:', 'wsklad'),
				wsklad()->settings()->get('php_post_max_size', wsklad()->environment()->get('php_post_max_size')),
				__('Can only decrease the value, because it must not exceed the limits from the WSKLAD settings.', 'wsklad')
			),
			'default' => wsklad()->settings()->get('php_post_max_size', wsklad()->environment()->get('php_post_max_size')),
			'css' => 'min-width: 100px;',
		];

        $fields['php_max_execution_time'] =
        [
            'title' => __('Maximum time for execution PHP', 'wsklad'),
            'type' => 'text',
            'description' => sprintf
            (
                '%s <br /> %s <b>%s</b> <br /> %s',
                __('Value is seconds. Algorithms of current account will run until a time limit is end.', 'wsklad'),
                __('Current WSKLAD limit:', 'wsklad'),
                wsklad()->settings()->get('php_max_execution_time', wsklad()->environment()->get('php_max_execution_time')),
                __('If specify 0, the time limit will be disabled. Specifying 0 is not recommended, it is recommended not to exceed the WSKLAD limit.', 'wsklad')
            ),
            'default' => wsklad()->settings()->get('php_max_execution_time', wsklad()->environment()->get('php_max_execution_time')),
            'css' => 'min-width: 100px;',
        ];

		return $fields;
	}

    /**
     * Sidebar show
     */
    public function outputSidebar()
    {
        $account = $this->getAccount();

		$this->connectionTypeNotice($account);

		$account_options = $account->getOptions();
		if(isset($account_options['logger_level']))
		{
			$args =
			[
				'object' => $this
			];

            if((int)$account_options['logger_level'] === 100)
            {
				$args['type'] = 'danger';
				$args['header'] = '<h4 class="alert-heading mt-0 mb-1">' . esc_html__('Debug is enabled!', 'wsklad') . '</h4>';
				$args['body'] = esc_html__('The current account has debug mode enabled. You must disable this mode after debugging is complete.', 'wsklad');
            }

            if((int)$account_options['logger_level'] === 200)
            {
				$args['type'] = 'warning';
				$args['header'] = '<h4 class="alert-heading mt-0 mb-1">' . esc_html__('Info is enabled!', 'wsklad') . '</h4>';
				$args['body'] = esc_html__('The extended information recording mode is enabled for the current account. It is recommended to disable this mode after debugging is complete.', 'wsklad');
            }

            if((int)$account_options['logger_level'] <= 200)
            {
                wsklad()->views()->getView('accounts/sidebar_alert_item.php', $args);
            }
        }
    }

	/**
	 * Warn about the login-and-password connection mode.
	 *
	 * From 01.12.2026 Moy Sklad counts a Basic Auth request as 4 units of the rate limit
	 * instead of 1, while a permanent token still costs 1. In practice the ceiling drops
	 * from 45 requests per 3 seconds to 11 — a four-fold slowdown that looks like the
	 * sync has simply become slow, with nothing in the log to explain it.
	 *
	 * The warning is informational: existing accounts keep working in login mode, which
	 * is the point of the 0.x compatibility promise.
	 *
	 * @param Account $account
	 *
	 * @return void
	 */
	private function connectionTypeNotice($account)
	{
		if('login' !== $account->getConnectionType())
		{
			return;
		}

		$wsklad_token_url = 'https://online.moysklad.ru/app/settings/integrations/tokens';

		$args =
		[
			'object' => $this,
			'type' => 'warning',
			'header' => '<h4 class="alert-heading mt-0 mb-1">' . esc_html__('Slower authorization', 'wsklad') . '</h4>',
			'body' => sprintf
			(
				'<p>%s</p><p>%s <a href="%s" target="_blank" rel="noopener noreferrer">%s</a></p>',
				esc_html__('This account connects by login and password. Since December 2026 Moy Sklad counts each such request as 4 units of the rate limit instead of 1, so this account can make roughly four times fewer requests per second. Switching to a permanent token restores the full rate.', 'wsklad'),
				esc_html__('You can issue a token at any time — the login keeps working until you switch.', 'wsklad'),
				esc_url($wsklad_token_url),
				esc_html__('Open Moy Sklad token settings', 'wsklad')
			)
		];

		wsklad()->views()->getView('accounts/sidebar_alert_item.php', $args);

		$account->log()->warning
		(
			'Account uses login/password authorization: rate limit is 4x lower than with a token.',
			['account_id' => $account->getId()]
		);
	}
}