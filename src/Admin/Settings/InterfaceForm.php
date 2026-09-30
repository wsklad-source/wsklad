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
	 * The section stays registered, but it is no longer offered in the tab bar and
	 * it no longer draws a form - see `outputForm()`. Both halves matter: a
	 * bookmarked link still has to land somewhere honest, and a section the bar
	 * still advertises is a promise the tab cannot keep.
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_interface($fields): array
	{
		return $fields;
	}

	/**
	 * Explain the empty tab instead of rendering one.
	 *
	 * The inherited renderer draws the field table and a "Save settings" button.
	 * With no fields it drew an empty table under a button that saves nothing - a
	 * control promising an effect it does not have, which reads as a broken page
	 * rather than as a retired one.
	 *
	 * Overriding the renderer is enough; the settings are untouched either way.
	 * `init()` still runs above this and still loads the option, still keeps the
	 * legacy keys, and still preserves them on a save, because none of that is
	 * about drawing anything.
	 */
	public function outputForm()
	{
		wsklad()->views()->getView('settings/notice.php');
	}
}
