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

		$form = new UpdateForm();

		$form_data = $account->get_options();
		$form_data['status'] = $account->get_status();

		$form->load_saved_data($form_data);

		if(isset($_GET['form']) && $_GET['form'] === $form->get_id())
		{
			$data = $form->save();

			if($data)
			{
				$account->set_status($data['status']);
				unset($data['status']);

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

		add_action('wsklad_admin_accounts_update_sidebar_show', [$this, 'outputSidebar'], 10);
		add_action('wsklad_admin_accounts_update_show', [$form, 'output_form'], 10);
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

	/**
	 * Sidebar show
	 */
	public function outputSidebar()
	{
		$account = $this->getAccount();

		$args =
		[
			'header' => '<h3 class="p-0 m-0">' . __('About account', 'wsklad') . '</h3>',
			'object' => $this
		];

		$body = '<ul class="list-group m-0 list-group-flush">';
		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('ID: ', 'wsklad') . $account->get_id();
		$body .= '</li>';

		$body .= '<li class="list-group-item p-2 m-0">';
		$user_id = $account->get_user_id();
		$user = get_userdata($user_id);
		if($user instanceof \WP_User && $user->exists())
		{
			$body .= __('User: ', 'wsklad') . $user->get('nickname') . ' (' . $user_id. ')';
		}
		else
		{
			$body .= __('User is not exists.', 'wsklad');
		}
		$body .= '</li>';

		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('Date create: ', 'wsklad') . $this->utilityPrettyDate($account->get_date_create());
		$body .= '</li>';
		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('Date modify: ', 'wsklad') . $this->utilityPrettyDate($account->get_date_modify());
		$body .= '</li>';
		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('Date active: ', 'wsklad') . $this->utilityPrettyDate($account->get_date_activity());
		$body .= '</li>';

		$body .= '</ul>';

		$args['body'] = $body;
		//$args['css'] = 'margin-top:-35px!important;';

		wsklad()->views()->getView('accounts/update_sidebar_item.php', $args);
	}
}