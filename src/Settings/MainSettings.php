<?php namespace Wsklad\Settings;

defined('ABSPATH') || exit;

use Wsklad\Abstracts\SettingsAbstract;

/**
 * Class MainSettings
 *
 * @package Wsklad\Settings
 */
class MainSettings extends SettingsAbstract
{
	/**
	 * Keys this option group still carries, but no longer renders.
	 *
	 * `api_moysklad_timeout` was offered on the Main tab and read by nothing, so
	 * the field is gone. The key itself is not.
	 *
	 * It stays declared here, and stays in the stored `wsklad_settings_main`
	 * option, for two reasons. An install upgrading from 0.10.x still has the key in
	 * its database, and the stored value must remain readable — both to the plugin
	 * and to an extension reading `settings()->get('api_moysklad_timeout')`.
	 *
	 * And it is going to be used. 1.x brings its own HTTP library, and that library
	 * is what will read this option; today there is no client in 0.x to wire it to,
	 * which is why the field promised a control with no effect. Deleting the key
	 * now would only hand 1.x a change to undo. Restoring a visible field in 0.x
	 * would be the same defect it already was: a checkbox that changes nothing.
	 *
	 * @var array
	 */
	const LEGACY_DEFAULTS =
	[
		'api_moysklad_timeout' => '30',
	];

	/**
	 * Main constructor.
	 */
	public function __construct()
	{
		$this->setOptionName('main');
	}
}