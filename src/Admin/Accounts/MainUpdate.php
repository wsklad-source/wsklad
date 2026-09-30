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
					$info_message = __('Account saved.', 'wsklad');

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
					$error_message = __('Could not save the account. Check the fields and try again.', 'wsklad');

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
                __('Used to authorise requests to Moy Sklad for this account.', 'wsklad'),
                __('Issued in your Moy Sklad account.', 'wsklad')
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
				__('Issue one in your Moy Sklad account. Leave this field empty to keep the saved token.', 'wsklad'),
				__('Warning:', 'wsklad'),
				__('issuing a new token in Moy Sklad immediately invalidates the previous one. Every account still using the old token — here, on another site, or in another plugin — stops working at that moment.', 'wsklad'),
				__('Issue or revoke tokens here at any time:', 'wsklad'),
				esc_url('https://online.moysklad.ru/app/settings/integrations/tokens'),
				__('Open the Moy Sklad token settings', 'wsklad')
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
                __('Used to authorise requests to Moy Sklad for this account.', 'wsklad'),
                __('Issued in your Moy Sklad account.', 'wsklad')
            )
		];

		$fields['moysklad_login'] =
		[
			'title' => __('Username', 'wsklad'),
			'type' => 'text',
			'description' => __('The login for your Moy Sklad account. It cannot be changed once the account has been added.', 'wsklad'),
			'default' => '',
			'css' => 'min-width: 350px;',
			'class' => 'disabled',
			'disabled' => true
		];

		$fields['moysklad_password'] =
		[
			'title' => __('User password', 'wsklad'),
			'type' => 'password',
			'description' => __('The password for this Moy Sklad user. Leave the field empty to keep the saved password.', 'wsklad'),
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
			'description' => __('The event log for this account. View it in the log viewer extension, or over FTP.', 'wsklad'),
		];

		$fields['logger_level'] =
		[
			'title' => __('Event level', 'wsklad'),
			'type' => 'select',
			'description' => __('Events at the selected level and above are written to the log file. A higher level records less.', 'wsklad'),
			'default' => '300',
			'options' =>
				[
					'logger_level' => __('Use this level for main events', 'wsklad'),
					'100' => __('DEBUG (100)', 'wsklad'),
					'200' => __('INFO (200)', 'wsklad'),
					'250' => __('NOTICE (250)', 'wsklad'),
					'300' => __('WARNING (300)', 'wsklad'),
					'400' => __('ERROR (400)', 'wsklad'),
				],
		];

		$fields['logger_files_max'] =
		[
			'title' => __('Number of files', 'wsklad'),
			'type' => 'text',
			'description' => __('A new log file is created each day. Older files are deleted once this number is reached; by default that is 30 days of history.', 'wsklad'),
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
			'title' => __('Advanced', 'wsklad'),
			'type' => 'title',
			'description' => __('Limits applied to data processing for compatibility with your server.', 'wsklad'),
		];

		$fields['php_post_max_size'] =
		[
			'title' => __('Maximum request size', 'wsklad'),
			'type' => 'text',
			'description' => sprintf
			(
				'%s<br />%s <b>%s</b><br />%s',
				__('The largest request the plugin will accept from Moy Sklad, in bytes. A suffix may be used, for example 7M — M for megabytes, K for kilobytes, G for gigabytes.', 'wsklad'),
				__('Current WSKLAD limit:', 'wsklad'),
				wsklad()->settings()->get('php_post_max_size', wsklad()->environment()->get('php_post_max_size')),
				__('This value can only be lowered; it cannot exceed the limit from the WSKLAD settings.', 'wsklad')
			),
			'default' => wsklad()->settings()->get('php_post_max_size', wsklad()->environment()->get('php_post_max_size')),
			'css' => 'min-width: 100px;',
		];

        $fields['php_max_execution_time'] =
        [
            'title' => __('PHP execution time limit', 'wsklad'),
            'type' => 'text',
            'description' => sprintf
            (
                '%s <br /> %s <b>%s</b> <br /> %s',
                __('In seconds. This account\'s sync tasks stop when the limit is reached.', 'wsklad'),
                __('Current WSKLAD limit:', 'wsklad'),
                wsklad()->settings()->get('php_max_execution_time', wsklad()->environment()->get('php_max_execution_time')),
                __('A value of 0 removes the time limit. It is not recommended, and the WSKLAD limit should not be exceeded either.', 'wsklad')
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
				$args['header'] = '<h4 class="alert-heading mt-0 mb-1">' . esc_html__('Debug logging is on', 'wsklad') . '</h4>';
				$args['body'] = esc_html__('Debug logging is on for this account. Turn it off once you have finished debugging.', 'wsklad');
            }

            if((int)$account_options['logger_level'] === 200)
            {
				$args['type'] = 'warning';
				$args['header'] = '<h4 class="alert-heading mt-0 mb-1">' . esc_html__('Info logging is on', 'wsklad') . '</h4>';
				$args['body'] = esc_html__('Info logging is on for this account. Turn it off once you have finished debugging.', 'wsklad');
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
			'header' => '<h4 class="alert-heading mt-0 mb-1">' . esc_html__('Slower authorisation', 'wsklad') . '</h4>',
			'body' => sprintf
			(
				'<p>%s</p><p>%s <a href="%s" target="_blank" rel="noopener noreferrer">%s</a></p>',
				esc_html__('This account connects with a login and password. From December 2026 Moy Sklad counts each such request as 4 units of the rate limit instead of 1, so this account can make about four times fewer requests per second. Switching to a token restores the full rate.', 'wsklad'),
				esc_html__('You can issue a token at any time — the login and password keep working until you switch.', 'wsklad'),
				esc_url($wsklad_token_url),
				esc_html__('Open the Moy Sklad token settings', 'wsklad')
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