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
			'description' => __('Where and how the plugin reaches Moy Sklad. The defaults work for a normal Moy Sklad account; change them only if Moy Sklad itself told you to.', 'wsklad'),
		];

		$fields['api_moysklad_host'] =
		[
			'title' => __('Host', 'wsklad'),
			'type' => 'text',
			'description' => __('The address the plugin sends its requests to. Leave it as api.moysklad.ru unless Moy Sklad gave you a different one - a wrong value here breaks every connection, and there is no account that would fix it.', 'wsklad'),
			'default' => 'api.moysklad.ru',
			'css' => 'min-width: 255px;',
		];

		$fields['api_moysklad_force_https'] =
		[
			'title' => __('Force requests over HTTPS', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Send every request over HTTPS?', 'wsklad'),
			'description' => __('On is right for every normal site: it keeps your access token unreadable on the way to Moy Sklad. Turn it off only to debug a certificate problem, and turn it back on afterwards.', 'wsklad'),
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
			'description' => __('Extensions are separate add-ons that add their own screens and tools to WSKLAD. These two switches decide whether they are allowed to.', 'wsklad'),
		];

		$fields['extensions'] =
		[
			'title' => __('Loading extensions', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Let extensions add their own screens?', 'wsklad'),
			'description' => __('On: every installed extension may add its own pages to the WSKLAD menu. Off: none of them may - the pages they add simply will not appear. Extensions that came with WSKLAD itself keep working either way.', 'wsklad'),
			'default' => 'yes'
		];

		$fields['extensions_tools'] =
		[
			'title' => __('Loading tools from extensions', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Let extensions add their own tools?', 'wsklad'),
			'description' => __('On: extensions may add entries to the Tools section. Off: the Tools section holds only what WSKLAD provides. This switch is separate from the one above, so you can keep an extension\'s tools while hiding its pages.', 'wsklad'),
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
			'description' => __('An account is one Moy Sklad shop you connect to. You can add several. These settings decide how WSKLAD treats them.', 'wsklad'),
		];

		$fields['accounts_test_before_add'] =
		[
			'title' => __('Test the connection first', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Check each account works before adding it?', 'wsklad'),
			'description' => __('On: when you add an account, WSKLAD sends one test request to Moy Sklad and refuses the account if the credentials are wrong. That saves you finding out at checkout. Off: the account is saved as given, and a bad token is only noticed when the first real request fails.', 'wsklad'),
			'default' => 'yes'
		];

		$fields['accounts_unique_name'] =
		[
			'title' => __('Unique account names', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Give every account a different name?', 'wsklad'),
			'description' => __('On: two accounts cannot share a name, so it is always clear which shop an order came from. Off: duplicates are allowed, which makes the accounts list harder to read. Names are for you - they are not sent to Moy Sklad.', 'wsklad'),
			'default' => 'yes'
		];

		$fields['accounts_show_per_page'] =
		[
			'title' => __('Accounts per page', 'wsklad'),
			'type' => 'text',
			'description' => __('A number, not a word. Ten is enough for almost every shop; a larger list is only slower to load.', 'wsklad'),
			'default' => 10,
			'css' => 'min-width: 20px;',
		];

		$fields['accounts_draft_delete'] =
		[
			'title' => __('Skip the trash for drafts', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Delete draft accounts straight away, without the trash?', 'wsklad'),
			'description' => __('On: deleting a draft account erases it for good. Off: it goes to the trash first, so you can still get it back. This does not apply to a connected account - disconnect it first.', 'wsklad'),
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
            'description' => __('Two numbers your server decides. Raise them here only if a large import is being cut short - and never above what your server reports below.', 'wsklad'),
        ];

		$fields['php_max_execution_time'] =
        [
            'title' => __('PHP execution time limit', 'wsklad'),
            'type' => 'text',
            'description' => sprintf
            (
                '%s <br /> %s <b>%s</b> <br /> %s',
                __('In seconds. WSKLAD stops when this much time has passed, so a long import stops partway rather than running until your host kills it.', 'wsklad'),
                __('Your server allows:', 'wsklad'),
                wsklad()->environment()->get('php_max_execution_time'),
                __('0 means no limit at all. Leave it alone unless a large import is failing: the server limit above is the real ceiling, and setting this higher than that does nothing.', 'wsklad')
            ),
            'default' => wsklad()->environment()->get('php_max_execution_time'),
            'css' => 'min-width: 100px;',
        ];

$fields['php_post_max_size'] =
        [
            'title' => __('Maximum request size', 'wsklad'),
            'type' => 'text',
            'description' => sprintf
            (
                '%s <br /> %s <b>%s</b> <br /> %s',
                __('How much data the plugin may send in one request.', 'wsklad'),
                __('Your server allows:', 'wsklad'),
                wsklad()->environment()->get('php_post_max_size'),
                __('It is a size, not a number: write 20M for twenty megabytes. Going over what the server allows does not make the plugin faster - the request is rejected before it leaves.', 'wsklad')
            ),
            'default' => wsklad()->environment()->get('php_post_max_size'),
            'css' => 'min-width: 100px;',
        ];

		return $fields;
	}
}