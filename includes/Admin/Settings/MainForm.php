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
		$this->set_id('settings-main');
		$this->setSettings(new MainSettings());

		add_filter(WSKLAD_PREFIX . $this->get_id() . '_form_load_fields', [$this, 'init_fields_main'], 10);
		add_filter(WSKLAD_PREFIX . $this->get_id() . '_form_load_fields', [$this, 'init_fields_api_moysklad'], 10);
		add_filter(WSKLAD_PREFIX . $this->get_id() . '_form_load_fields', [$this, 'init_fields_interface'], 10);
		add_filter(WSKLAD_PREFIX . $this->get_id() . '_form_load_fields', [$this, 'init_fields_enable_data'], 10);
		add_filter(WSKLAD_PREFIX . $this->get_id() . '_form_load_fields', [$this, 'init_fields_logger'], 10);

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
		$fields['accounts_unique_name'] =
		[
			'title' => __('Unique connection names', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Require unique connection names for accounts?', 'wsklad'),
			'description' => __('If enabled, you will need to provide unique names when connecting accounts.', 'wsklad'),
			'default' => 'yes'
		];

		$fields['accounts_test_before_add'] =
		[
			'title' => __('Test connection before add', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Enable data validation to connect to Moy Sklad before adding?', 'wsklad'),
			'description' => __('If enabled, then when connecting accounts from Moy Sklad, they will be checked for validity by a test connection.', 'wsklad'),
			'default' => 'yes'
		];

		return $fields;
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
		$fields['interface_title'] =
		[
			'title' => __('Interface', 'wsklad'),
			'type' => 'title',
			'description' => __('Settings for the user interface.', 'wsklad'),
		];

		$fields['admin_inject'] =
		[
			'title' => __('Changing the interface', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Allow changes to WordPress and WooCommerce dashboard interface?', 'wsklad'),
			'description' => __('If enabled, new features will appear in the WordPress and WooCommerce interface according to the interface change settings.', 'wsklad'),
			'default' => 'yes'
		];

		return $fields;
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

		return $fields;
	}

	/**
	 * Add settings for logger
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_logger($fields)
	{
		$fields['logger_title'] =
		[
			'title' => __('Technical events', 'wsklad'),
			'type' => 'title',
			'description' => __('Used by technical specialists. Can leave it at that.', 'wsklad'),
		];

		$fields['logger_level'] =
		[
			'title' => __('Level', 'wsklad'),
			'type' => 'select',
			'description' => __('All events of the selected level will be recorded in the log file. The higher the level, the less data is recorded.', 'wsklad'),
			'default' => '300',
			'options' =>
			[
				'' => __('Off', 'wsklad'),
				'100' => __('DEBUG', 'wsklad'),
				'200' => __('INFO', 'wsklad'),
				'250' => __('NOTICE', 'wsklad'),
				'300' => __('WARNING', 'wsklad'),
				'400' => __('ERROR', 'wsklad'),
			]
		];

		$fields['logger_wsklad'] =
		[
			'title' => __('Access to technical events', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Allow the WSklad team to access technical events?', 'wsklad'),
			'description' => __('If allowed, the WSklad team will be able to access technical events and release the necessary updates based on them.', 'wsklad'),
			'default' => 'no'
		];

		return $fields;
	}

	/**
	 * Add settings for enabled data
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_enable_data($fields)
	{
		$fields['title_enable_data'] =
		[
			'title' => __('Enable data by objects', 'wsklad'),
			'type' => 'title',
			'description' => __('Specifying the ability to work with data by object types (data types).', 'wsklad'),
		];

		$fields['enable_data_products'] =
		[
			'title' => __('Products', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Enable', 'wsklad'),
			'description' => __('Ability to work with products (delete, change, add).', 'wsklad'),
			'default' => 'no'
		];

		$fields['enable_data_category'] =
		[
			'title' => __('Categories', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Enable', 'wsklad'),
			'description' => __('Ability to work with categories (delete, change, add).', 'wsklad'),
			'default' => 'no'
		];

		$fields['enable_data_attributes'] =
		[
			'title' => __('Attributes', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Enable', 'wsklad'),
			'description' => __('Ability to work with attributes (delete, change, add).', 'wsklad'),
			'default' => 'no'
		];

		$fields['enable_data_orders'] =
		[
			'title' => __('Orders', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Enable', 'wsklad'),
			'description' => __('Ability to work with orders (delete, change, add).', 'wsklad'),
			'default' => 'no'
		];

		$fields['enable_data_images'] =
		[
			'title' => __('Images', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Enable', 'wsklad'),
			'description' => __('Ability to work with images (delete, change, add).', 'wsklad'),
			'default' => 'no'
		];

		return $fields;
	}
}