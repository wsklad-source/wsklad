<?php namespace Wsklad\Admin\Connections;

defined('ABSPATH') || exit;

use Exception;

/**
 * Class ByLoginForm
 *
 * @package Wsklad\Admin\Connections
 */
class ByLoginForm extends Form
{
	/**
	 * CreateForm constructor.
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->setId('accounts-create');

		add_filter('wsklad_' . $this->getId() . '_form_load_fields', [$this, 'init_fields_main'], 10);

		$this->init();
	}

	/**
	 * Add for Main
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function init_fields_main(array $fields): array
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
}