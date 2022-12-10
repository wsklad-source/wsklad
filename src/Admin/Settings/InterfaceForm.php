<?php namespace Wsklad\Admin\Settings;

defined('ABSPATH') || exit;

use Wsklad\Exceptions\Exception;
use Wsklad\Settings\InterfaceSettings;

/**
 *  InterfaceForm
 *
 * @package Wsklad\Admin
 */
class InterfaceForm extends Form
{
	/**
	 * InterfaceForm constructor.
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->set_id('settings-interface');
		$this->setSettings(new InterfaceSettings());

		add_filter('wsklad_' . $this->get_id() . '_form_load_fields', [$this, 'init_fields_interface'], 10);

		$this->init();
	}
	/**
	 * Add for Interface
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_interface($fields)
	{
		$fields['admin_interface'] =
		[
			'title' => __('Changing the interface', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Allow changes to WordPress dashboard interface?', 'wsklad'),
			'description' => sprintf
			(
				'%s <hr>%s',
				__('If enabled, new features will appear in the WordPress interface according to the interface change settings.', 'wsklad'),
				__('If interface modification is enabled, it is possible to change settings for individual features, users, and roles. If disabled, features will be disabled globally for everyone and everything.', 'wsklad')
			),
			'default' => 'yes'
		];

		$fields['admin_interface_media_library_column'] =
		[
			'title' => __('Column in media library list', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Enable', 'wsklad'),
			'description' => __('Output of a column with information from 1C to the list of media files.', 'wsklad'),
			'default' => 'yes'
		];

		return $fields;
	}
}