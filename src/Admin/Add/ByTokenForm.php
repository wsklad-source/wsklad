<?php namespace Wsklad\Admin\Add;

defined('ABSPATH') || exit;

use Exception;

/**
 * Class ByTokenForm
 *
 * @package Wsklad\Admin\Add
 */
class ByTokenForm extends Form
{
	/**
	 * CreateForm constructor.
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->setId('add-by-token');

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