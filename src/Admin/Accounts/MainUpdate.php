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

		if($account->get_connection_type() === 'token')
		{
			add_filter('wsklad_accounts-update_form_load_fields', [$this, 'accountsFieldsToken'], 20, 1);
		}
		else
		{
			add_filter('wsklad_accounts-update_form_load_fields', [$this, 'accountsFieldsLoginAndPassword'], 20, 1);
		}

		$form = new UpdateForm();

		$form_data = $account->get_options();
		$form_data['status'] = $account->get_status();

		$form_data['moysklad_login'] = $account->get_moysklad_login();
		$form_data['moysklad_password'] = $account->get_moysklad_password();
		$form_data['moysklad_token'] = $account->get_moysklad_token();

		$form->loadSavedData($form_data);

		if(isset($_GET['form']) && $_GET['form'] === $form->getId())
		{
			$data = $form->save();

			if($data)
			{
				$account->set_status($data['status']);
				$account->set_moysklad_password($data['moysklad_password']);
				$account->set_moysklad_token($data['moysklad_token']);

				unset($data['status'], $data['moysklad_login'], $data['moysklad_password'], $data['moysklad_token']);

				$account->set_date_modify(time());
				$account->set_options($data);

				$saved = $account->save();

				if($saved)
				{
					wsklad()->admin()->notices()->create
					(
						[
							'type' => 'update',
							'data' => __('Account update success.', 'wsklad')
						]
					);
				}
				else
				{
					wsklad()->admin()->notices()->create
					(
						[
							'type' => 'error',
							'data' => __('Account update error. Please retry saving or change fields.', 'wsklad')
						]
					);
				}
			}
		}

		add_action('wsklad_admin_accounts_sections_single_show', [$form, 'outputForm'], 10);
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
			'title' => __('Данные для авторизации', 'wsklad'),
			'type' => 'title',
			'description' => __('Authorization of requests for current account.', 'wsklad'),
		];

		$fields['moysklad_token'] =
		[
			'title' => __('Token', 'wsklad'),
			'type' => 'text',
			'description' => __('Можно получить в личном кабинете на сайте Мой Склад. В дальнейшем необходимо следить за его актуальностью.', 'wsklad'),
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
			'title' => __('Данные для авторизации', 'wsklad'),
			'type' => 'title',
			'description' => __('Authorization of requests for current account.', 'wsklad')
		];

		$fields['moysklad_login'] =
		[
			'title' => __('Username', 'wsklad'),
			'type' => 'text',
			'description' => __('Логин от учетной записи Мой Склад. После добавления учетной записи изменение логина невозможно.', 'wsklad'),
			'default' => '',
			'css' => 'min-width: 350px;',
			'class' => 'disabled',
			'disabled' => true
		];

		$fields['moysklad_password'] =
		[
			'title' => __('User password', 'wsklad'),
			'type' => 'password',
			'description' => __('Пароль от указанного пользователя Мой Склад.', 'wsklad'),
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

		return $fields;
	}
}