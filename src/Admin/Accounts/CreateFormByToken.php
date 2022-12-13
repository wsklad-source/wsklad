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

/**
 * Class CreateFormByToken
 *
 * @package Wsklad\Admin\Accounts
 */
class CreateFormByToken extends Form
{
	/**
	 * CreateForm constructor.
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->set_id('accounts-create-by-token');

		add_filter('wsklad_' . $this->get_id() . '_form_load_fields', [$this, 'init_fields_main'], 10);

		$this->init();
	}

	/**
	 * Add for Main
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_main($fields)
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