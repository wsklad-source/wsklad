<?php namespace Wsklad\Admin\Settings;

defined('ABSPATH') || exit;

use Exception;
use Wsklad\Settings\MainSettings;

/**
 * Class MainForm
 *
 * @package Wsklad\Admin\Settings
 */
class MainForm extends Form
{
	/**
	 * MainForm constructor.
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->setId('settings-main');
		$this->setSettings(new MainSettings());

		add_filter('wsklad_' . $this->getId() . '_form_load_fields', [$this, 'init_fields_accounts'], 10);
		add_filter('wsklad_' . $this->getId() . '_form_load_fields', [$this, 'init_fields_technical'], 10);
		add_filter('wsklad_' . $this->getId() . '_form_load_fields', [$this, 'init_fields_api_moysklad'], 10);

		$this->init();
	}

	/**
	 * Add fields for MoySklad
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_api_moysklad($fields)
	{
		$fields['api_moysklad_title'] =
		[
			'title' => __('API MoySklad', 'wsklad'),
			'type' => 'title',
			'description' => __('Used for API connections.', 'wsklad'),
		];

		$fields['api_moysklad_host'] =
		[
			'title' => __('Host', 'wsklad'),
			'type' => 'text',
			'description' => __('This host is used for API connection. If the host is unknown, use the value: online.moysklad.ru', 'wsklad'),
			'default' => 'online.moysklad.ru',
			'css' => 'min-width: 255px;',
		];

		$fields['api_moysklad_force_https'] =
		[
			'title' => __('Force requests over HTTPS', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Enable HTTPS enforcement for requests to the MoySklad API?', 'wsklad'),
			'description' => __('If enabled, all API requests from the site to MoySklad will be made over the secure HTTPS protocol.', 'wsklad'),
			'default' => 'yes'
		];

		$fields['api_moysklad_timeout'] =
		[
			'title' => __('Timeout', 'wsklad'),
			'type' => 'text',
			'description' => __('This timeout is used for API connection. If the timeout is unknown, use the value: 30', 'wsklad'),
			'default' => '30',
			'css' => 'min-width: 111px;',
		];

		return $fields;
	}

	/**
	 * Add fields for Accounts
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_accounts($fields): array
	{
		$fields['accounts_title'] =
		[
			'title' => __('Accounts', 'wsklad'),
			'type' => 'title',
			'description' => __('Some settings for the accounts.', 'wsklad'),
		];

		$fields['accounts_test_before_add'] =
		[
			'title' => __('Test connection before add', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Enable data validation to connect to Moy Sklad before adding?', 'wsklad'),
			'description' => __('If enabled, then when connecting accounts from Moy Sklad, they will be checked for validity by a test connection.', 'wsklad'),
			'default' => 'yes'
		];

		$fields['accounts_unique_name'] =
		[
			'title' => __('Unique names', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Require unique names for accounts?', 'wsklad'),
			'description' => __('If enabled, will need to provide unique names for the accounts.', 'wsklad'),
			'default' => 'yes'
		];

		$fields['accounts_show_per_page'] =
		[
			'title' => __('Number in the list', 'wsklad'),
			'type' => 'text',
			'description' => __('The number of displayed accounts on one page.', 'wsklad'),
			'default' => 10,
			'css' => 'min-width: 20px;',
		];

		$fields['accounts_draft_delete'] =
		[
			'title' => __('Deleting drafts without trash', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Enable deleting drafts without placing them in the trash?', 'wsklad'),
			'description' => __('If enabled, accounts for connections in the draft status will be deleted without being added to the basket.', 'wc1c'),
			'default' => 'yes'
		];

		return $fields;
	}


	/**
	 * Add for Technical
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_technical($fields)
	{
		$fields['technical_title'] =
			[
				'title' => __('Technical settings', 'wsklad'),
				'type' => 'title',
				'description' => __('Used to set up the environment.', 'wsklad'),
			];

		$fields['php_max_execution_time'] =
			[
				'title' => __('Maximum time for execution PHP', 'wsklad'),
				'type' => 'text',
				'description' => sprintf
				(
					'%s <br /> %s <b>%s</b> <br /> %s',
					__('Value is seconds. wsklad will run until a time limit is set.', 'wsklad'),
					__('Server value:', 'wsklad'),
					wsklad()->environment()->get('php_max_execution_time'),
					__('If specify 0, the time limit will be disabled. Specifying 0 is not recommended, it is recommended not to exceed the server limit.', 'wsklad')
				),
				'default' => wsklad()->environment()->get('php_max_execution_time'),
				'css' => 'min-width: 100px;',
			];

		$fields['php_post_max_size'] =
			[
				'title' => __('Maximum request size', 'wsklad'),
				'type' => 'text',
				'description' => __('The setting must not take a size larger than specified in the server settings.', 'wsklad'),
				'default' => wsklad()->environment()->get('php_post_max_size'),
				'css' => 'min-width: 100px;',
			];

		return $fields;
	}
}