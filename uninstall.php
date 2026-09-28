<?php
/**
 * Root uninstall entry point.
 *
 * WordPress.org's plugin scanner and the "Delete" button on the Plugins screen both
 * look for a file named `uninstall.php` in the plugin root. Without it, deleting the
 * plugin leaves every table, option and log file behind.
 *
 * Removal is soft by default. A full wipe needs explicit consent, because the plugin
 * holds Moy Sklad credentials and WooCommerce mappings that cannot be recovered:
 *
 *   add_option('wsklad_uninstall_remove_data', 'yes');
 *   // …or, before deleting the plugin:
 *   define('WSKLAD_UNINSTALL_REMOVE_DATA', true);
 *
 * @package Wsklad
 * @since 0.10.1
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

if(!defined('WSKLAD_PLUGIN_FILE'))
{
	define('WSKLAD_PLUGIN_FILE', __FILE__);
}

$wsklad_autoload = __DIR__ . '/vendor/autoload.php';

if(!is_readable($wsklad_autoload))
{
	// Nothing can be cleaned up without the autoloader, and a fatal here would leave
	// the site in a broken state. Bailing out is the safer failure.
	return;
}

require_once $wsklad_autoload;

\Wsklad\Uninstall::instance();
