<?php
/**
 * Namespace
 */
namespace Wsklad\Admin\Settings;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Exception;
use Wsklad\Settings\ConnectionSettings;

/**
 * Class ConnectionForm
 *
 * @package Wsklad\Admin\Settings
 */
class ConnectionForm extends Form
{
	/**
	 * ConnectionForm constructor.
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->set_id('settings-connection');

		$connectionSettings = new ConnectionSettings();

		$this->setSettings($connectionSettings);

		add_action(WSKLAD_ADMIN_PREFIX . 'show', [$this, 'before_form_show'], 10);

		if($connectionSettings->isConnected())
		{
			add_filter(WSKLAD_PREFIX . $this->get_id() . '_form_load_fields', [$this, 'init_fields_connected'], 10);
		}
		else
		{
			add_filter(WSKLAD_PREFIX . $this->get_id() . '_form_load_fields', [$this, 'init_fields_main'], 10);
		}

		$this->init();
	}

	/**
	 * Show description
	 */
	public function before_form_show()
	{
		wsklad_get_template('/connection/description.php');
	}

	/**
	 * Connected fields
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_connected($fields)
	{
		$fields['connected_title'] =
		[
			'title' => __('Site is connected to WSklad', 'wsklad'),
			'type' => 'title',
			'description' => __('To create a new connection, need to disconnect the current connection.', 'wsklad'),
		];

		$fields['login'] =
		[
			'title' => __('Login', 'wsklad'),
			'type' => 'text',
			'description' => __('Connected login from the WSklad website.', 'wsklad'),
			'default' => '',
			'disabled' => true,
			'css' => 'min-width: 300px;',
		];

		$fields['token'] =
		[
			'title' => __('App token', 'wsklad'),
			'type' => 'text',
			'description' => __('The current application token for the user. This token can be revoked in your personal account on the WSklad website, as well as by clicking the Disconnect from WSklad button.', 'wsklad'),
			'default' => '',
			'disabled' => true,
			'css' => 'min-width: 300px;',
		];

		return $fields;
	}

	/**
	 * Main fields
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_main($fields)
	{
		$fields['main_title'] =
		[
			'title' => __('Site is not connected to WSklad', 'wsklad'),
			'type' => 'title',
			'description' => __('To create a new connection, need to enter a username and password from the WSklad website, or follow the link and authorize the application on the WSklad website.', 'wsklad'),
		];

		$fields['login'] =
		[
			'title' => __('Login', 'wsklad'),
			'type' => 'text',
			'description' => __('The login when registering on the WSklad website.', 'wsklad'),
			'default' => '',
			'css' => 'min-width: 300px;',
		];

		$fields['password'] =
		[
			'title' => __('Password', 'wsklad'),
			'type' => 'text',
			'description' => __('The current password on the WSklad site for the user. This password is not saved on site. A token for the application will be generated instead.', 'wsklad'),
			'default' => '',
			'css' => 'min-width: 300px;',
		];

		return $fields;
	}

	/**
	 * Form show
	 */
	public function output_form()
	{
		$args =
			[
				'object' => $this
			];

		wsklad_get_template('connection/form.php', $args);
	}
}