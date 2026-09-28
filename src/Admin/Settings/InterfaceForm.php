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
		$this->setId('settings-interface');
		$this->setSettings(new InterfaceSettings());

		add_filter('wsklad_' . $this->getId() . '_form_load_fields', [$this, 'init_fields_interface'], 10);

		$this->init();
	}

	/**
	 * Add for Interface
	 *
	 * Nothing is declared here any more. `admin_interface` and
	 * `admin_interface_media_library_column` used to be offered on this tab and
	 * were read by nothing: no gate, no media-library column and no branch
	 * anywhere in `src/` or `views/` ever looked at either key. They are kept as
	 * inert data in `InterfaceSettings::LEGACY_DEFAULTS` so an upgrading install
	 * does not lose the stored value.
	 *
	 * The tab and the class are kept. Removing them would take the option group
	 * `wsklad_settings_interface` with them, and a patch release removes nothing.
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_interface($fields): array
	{
		return $fields;
	}
}