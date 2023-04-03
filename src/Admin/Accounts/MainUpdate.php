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
		$form_data['moysklad_password'] = $account->getMoyskladPassword();
		$form_data['moysklad_token'] = $account->getMoyskladToken();

		$form->loadSavedData($form_data);

		if(isset($_GET['form']) && $_GET['form'] === $form->getId())
		{
			$data = $form->save();

			if($data)
			{
                // Галка стоит
                if($data['status'] === 'yes')
                {
                    if($account->isEnabled() === false)
                    {
                        $account->setStatus('active');
                    }
                }
                // галка не стоит
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
			'type' => 'text',
			'description' => __('Get it in account on the Moy Sklad. In the future, it is necessary to monitor its relevance.', 'wsklad'),
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
			'description' => __('Password for the specified user Moy Sklad.', 'wsklad'),
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

        $account_options = $account->getOptions();
        if(isset($account_options['logger_level']))
        {
            if((int)$account_options['logger_level'] === 100)
            {
                $args =
                [
                    'type' => 'danger',
                    'header' => '<h4 class="alert-heading mt-0 mb-1">' . __('Debug is enabled!', 'wsklad') . '</h4>',
                    'object' => $this,
                    'body' => __('The current account has debug mode enabled. You must disable this mode after debugging is complete.', 'wsklad')
                ];
            }

            if((int)$account_options['logger_level'] === 200)
            {
                $args =
                [
                    'type' => 'warning',
                    'header' => '<h4 class="alert-heading mt-0 mb-1">' . __('Info is enabled!', 'wsklad') . '</h4>',
                    'object' => $this,
                    'body' => __('The extended information recording mode is enabled for the current account. It is recommended to disable this mode after debugging is complete.', 'wsklad')
                ];
            }

            if((int)$account_options['logger_level'] <= 200)
            {
                wsklad()->views()->getView('accounts/sidebar_alert_item.php', $args);
            }
        }
    }
}