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
	 * `api_moysklad_timeout` was offered on the Main tab and read by nothing. The
	 * request timeout that actually applies is `Adapter\Http\WpHttpClient::$timeout`,
	 * a property on the client, and it is not wired to this option — so the field
	 * promised a control that had no effect and has been removed from the form.
	 *
	 * It stays declared here, and stays in the stored `wsklad_settings_main`
	 * option, because an install upgrading from 0.10.x still has the key in its
	 * database. The stored value must remain readable — both to the plugin and to
	 * an extension reading `settings()->get('api_moysklad_timeout')` — and this is
	 * a patch release, so nothing may be removed. Wiring the client to the option
	 * instead of removing the field is a behaviour change and belongs in a minor
	 * release, not here.
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