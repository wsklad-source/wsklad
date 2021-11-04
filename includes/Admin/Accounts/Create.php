<?php
/**
 * Namespace
 */
namespace Wsklad\Admin\Accounts;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Exception;
use Wsklad\Abstracts\ScreenAbstract;
use Wsklad\Traits\Singleton;

/**
 * Class Create
 *
 * @package Wsklad\Admin\Accounts
 */
class Create extends ScreenAbstract
{
	/**
	 * Traits
	 */
	use Singleton;

	/**
	 * Create constructor
	 */
	public function __construct()
	{
		parent::__construct();

		$create_form = new CreateForm();
		$create_form->load_fields($this->create_form_fields());
		if(!empty($create_form->get_posted_data()))
		{
			$create_form->save();
		}

		$create_form_by_token = new CreateFormByToken();
		$create_form_by_token->load_fields($this->create_form_fields_by_token());
		if(!empty($create_form_by_token->get_posted_data()))
		{
			$create_form_by_token->save();
		}

		add_action(WSKLAD_ADMIN_PREFIX . 'accounts_form_create_by_account_show', [$create_form, 'output_form'], 10);
		add_action(WSKLAD_ADMIN_PREFIX . 'accounts_form_create_by_token_show', [$create_form_by_token, 'output_form'], 10);
	}

	/**
	 * Show page
	 *
	 * @return void
	 */
	public function output()
	{
		wsklad_get_template('accounts/create.php');
	}

	/**
	 * Fields for form
	 *
	 * @return array
	 */
	public function create_form_fields()
	{
		$fields['title_main'] =
		[
			'title' => __('Connect by Login & Password', 'wsklad'),
			'type' => 'title',
			'description' => __('Connection to MoySklad by login and password.', 'wsklad'),
		];

		$fields['login'] =
		[
			'title' => __('Login', 'wsklad'),
			'type' => 'text',
			'description' => __('This login is used to enter the MoySklad service.', 'wsklad'),
			'default' => '',
			'css' => 'width: 100%;',
		];

		$fields['password'] =
		[
			'title' => __('Password', 'wsklad'),
			'type' => 'text',
			'description' => __('Password from the entered login to enter the MoySklad service.', 'wsklad'),
			'default' => '',
			'css' => 'width: 100%;',
		];

		return $fields;
	}

	/**
	 * Fields for form by token
	 *
	 * @return array
	 */
	public function create_form_fields_by_token()
	{
		$fields['title_token'] =
		[
			'title' => __('Connect by Token', 'wsklad'),
			'type' => 'title',
			'description' => __('Connection to MoySklad using a token generated on the MoySklad side.', 'wsklad'),
		];

		$fields['token'] =
		[
			'title' => __('Token', 'wsklad'),
			'type' => 'text',
			'description' => __('The token can be generated in MoySklad. After generating it, you must enter it and click on the button for connection.', 'wsklad'),
			'default' => '',
			'css' => 'width: 100%;',
		];

		return $fields;
	}
}