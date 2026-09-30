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
		add_filter('wsklad_' . $this->getId() . '_form_load_fields', [$this, 'init_fields_extensions'], 10);

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
			'title' => __('Moy Sklad API', 'wsklad'),
			'type' => 'title',
			'description' => __('Connection to the Moy Sklad API.', 'wsklad'),
		];

		$fields['api_moysklad_host'] =
		[
			'title' => __('Host', 'wsklad'),
			'type' => 'text',
			'description' => __('The host the plugin connects to. Unless you know otherwise, leave this as api.moysklad.ru.', 'wsklad'),
			'default' => 'api.moysklad.ru',
			'css' => 'min-width: 255px;',
		];

		$fields['api_moysklad_force_https'] =
		[
			'title' => __('Force requests over HTTPS', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Require HTTPS for API requests?', 'wsklad'),
			'description' => __('When on, every request from the site to Moy Sklad goes over HTTPS.', 'wsklad'),
			'default' => 'yes'
		];

		return $fields;
	}

	/**
	 * Add fields for Extensions
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_extensions($fields): array
	{
		$fields['extensions_title'] =
		[
			'title' => __('Extensions', 'wsklad'),
			'type' => 'title',
			'description' => __('Controls which installed extensions may add their own screens.', 'wsklad'),
		];

		$fields['extensions'] =
		[
			'title' => __('Loading extensions', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Let extensions add their own screens?', 'wsklad'),
			'description' => __('When on, extensions hooked into the "wsklad_extensions_loading" filter can add their own screens. When off, the filter is not applied and only the extensions that came with the plugin are shown.', 'wsklad'),
			'default' => 'yes'
		];

		$fields['extensions_tools'] =
		[
			'title' => __('Loading tools from extensions', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Let extensions add their own tools?', 'wsklad'),
			'description' => __('When on, extensions hooked into the "wsklad_load_tools" filter can add their own tools to the Moy Sklad section. When off, the filter is not applied and only the tools that came with the plugin are shown.', 'wsklad'),
			'default' => 'yes'
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
			'description' => __('How accounts behave.', 'wsklad'),
		];

		$fields['accounts_test_before_add'] =
		[
			'title' => __('Test the connection first', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Verify each account against Moy Sklad when it is added?', 'wsklad'),
			'description' => __('When on, each account is verified with a test request as soon as it is added.', 'wsklad'),
			'default' => 'yes'
		];

		$fields['accounts_unique_name'] =
		[
			'title' => __('Unique account names', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Require a different name for every account?', 'wsklad'),
			'description' => __('When on, two accounts cannot share a name.', 'wsklad'),
			'default' => 'yes'
		];

		$fields['accounts_show_per_page'] =
		[
			'title' => __('Accounts per page', 'wsklad'),
			'type' => 'text',
			'description' => __('How many accounts to show on one page.', 'wsklad'),
			'default' => 10,
			'css' => 'min-width: 20px;',
		];

		$fields['accounts_draft_delete'] =
		[
			'title' => __('Skip the trash for drafts', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Delete draft accounts permanently, without moving them to the trash?', 'wsklad'),
			'description' => __('When on, deleting a draft account removes it permanently instead of moving it to the trash.', 'wsklad'),
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
	public function init_fields_technical($fields): array
    {
		$fields['technical_title'] =
        [
            'title' => __('Technical settings', 'wsklad'),
            'type' => 'title',
            'description' => __('Limits that depend on what your server allows.', 'wsklad'),
        ];

		$fields['php_max_execution_time'] =
        [
            'title' => __('PHP execution time limit', 'wsklad'),
            'type' => 'text',
            'description' => sprintf
            (
                '%s <br /> %s <b>%s</b> <br /> %s',
                __('In seconds. WSKLAD stops at the time limit.', 'wsklad'),
                __('Server limit:', 'wsklad'),
                wsklad()->environment()->get('php_max_execution_time'),
                __('A value of 0 removes the time limit. It is not recommended, and the server limit should not be exceeded either.', 'wsklad')
            ),
            'default' => wsklad()->environment()->get('php_max_execution_time'),
            'css' => 'min-width: 100px;',
        ];

		$fields['php_post_max_size'] =
        [
            'title' => __('Maximum request size', 'wsklad'),
            'type' => 'text',
            'description' => __('This must not exceed the limit set on the server.', 'wsklad'),
            'default' => wsklad()->environment()->get('php_post_max_size'),
            'css' => 'min-width: 100px;',
        ];

		return $fields;
	}
}