<?php namespace Wsklad\Settings;

defined('ABSPATH') || exit;

use Wsklad\Abstracts\SettingsAbstract;

/**
 * InterfaceSettings
 *
 * @package Wsklad\Settings
 */
class InterfaceSettings extends SettingsAbstract
{
	/**
	 * Keys this option group still carries, but no longer renders.
	 *
	 * `admin_interface` and `admin_interface_media_library_column` were offered on
	 * the Interface tab and were read by nothing: no gate, no column, no branch
	 * anywhere in `src/` or `views/` ever looked at either one. A checkbox a user
	 * can tick and that changes no behaviour is worse than no checkbox, so the
	 * form fields are gone.
	 *
	 * They stay declared here, and stay in the stored `wsklad_settings_interface`
	 * option, for two reasons. An install upgrading from 0.10.x still has them in
	 * its database, and the value the user picked must not silently disappear the
	 * first time they save the settings. And an extension may read the key through
	 * `settings()->get()`; that read must keep returning the stored value instead
	 * of `null`. Deleting the key from the option would make both of those worse,
	 * and this is a patch release, so nothing may be removed.
	 *
	 * These values are inert. Nothing in the plugin reads them, which is the point:
	 * the map exists to keep the data, not to reintroduce the settings.
	 *
	 * @var array
	 */
	const LEGACY_DEFAULTS =
	[
		'admin_interface' => 'yes',
		'admin_interface_media_library_column' => 'yes',
	];

	/**
	 * InterfaceSettings constructor.
	 */
	public function __construct()
	{
		$this->setOptionName('interface');
	}
}