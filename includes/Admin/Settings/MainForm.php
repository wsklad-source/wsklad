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
		$fields['admin_inject'] =
		[
			'title' => __('Changing the interface', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Allow changes to WordPress and WooCommerce dashboard interface?', 'wsklad'),
			'description' => __('If enabled, new features will appear in the WordPress and WooCommerce interface according to the interface change settings.', 'wsklad'),
			'default' => 'yes'
		];

		$fields['extensions'] =
		[
			'title' => __('Extensions support', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Enable extension support?', 'wsklad'),
			'description' => __('If extension support is disabled, all third-party extensions will be unavailable.', 'wsklad'),
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
			'description' => __('Используются для подключений по API.', 'wsklad'),
		];

		$fields['api_moysklad_host'] =
		[
			'title' => __('Host', 'wsklad'),
			'type' => 'text',
			'description' => __('Данный хост используется для подключения по API. Если хост неизвестен, используйте значение: online.moysklad.ru', 'wsklad'),
			'default' => 'online.moysklad.ru',
			'css' => 'min-width: 255px;',
		];

		$fields['api_moysklad_force_https'] =
		[
			'title' => __('Форсировать запросы через HTTPS', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Включить принудительное использование HTTPS для запросов к API Мой Склад?', 'wsklad'),
			'description' => __('Если включено, все запросы по API с сайта в Мой Склад будут производится по защищенному протоколу HTTPS.', 'wsklad'),
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