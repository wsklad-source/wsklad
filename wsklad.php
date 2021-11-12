<?php
/**
 * Plugin Name: WSklad
 * Plugin URI: https://wsklad.ru
 * Description: Implementation of a mechanism for flexible exchange of various data between Moy Sklad and a site running WordPress using the WooCommerce plugin.
 * Version: 0.1.0
 * WC requires at least: 3.5
 * WC tested up to: 5.9
 * Requires at least: 4.7
 * Requires PHP: 5.6
 * Text Domain: wsklad
 * Domain Path: /languages
 * Copyright: WSklad team © 2019-2021
 * Author: WSklad team
 * Author URI: https://wsklad.ru
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Wsklad
 **/
defined('ABSPATH') || exit;

if(false === defined('WSKLAD_PREFIX'))
{
	define('WSKLAD_PREFIX', 'wsklad_');
	define('WSKLAD_ADMIN_PREFIX', 'wsklad_admin_');

	define('WSKLAD_PLUGIN_FILE', __FILE__);
	define('WSKLAD_PLUGIN_PATH', plugin_dir_path(WSKLAD_PLUGIN_FILE));
	define('WSKLAD_PLUGIN_URL', plugin_dir_url(__FILE__));
	define('WSKLAD_PLUGIN_NAME', plugin_basename(WSKLAD_PLUGIN_FILE));

	include_once __DIR__ . '/includes/functions.php';
	include_once __DIR__ . '/includes/Autoloader.php';

	$loader = new Wsklad\Autoloader();

	$loader->addNamespace('Wsklad', __DIR__ . '/includes');
	$loader->addNamespace('Digiom\WordPress\Admin\Notices', __DIR__ . '/vendor/digiom/notices-wp/Notices');
	$loader->addNamespace('Psr\Log', __DIR__ . '/vendor/psr/Log');
	$loader->addNamespace('Psr\Http\Message', __DIR__ . '/vendor/psr/http-message/src');
	$loader->addNamespace('Monolog', __DIR__ . '/vendor/monolog/src/Monolog');

	$loader->register();

	register_activation_hook(WSKLAD_PLUGIN_FILE, 'wsklad_activation');
	register_deactivation_hook(WSKLAD_PLUGIN_FILE, 'wsklad_deactivation');

	add_action('plugins_loaded', 'wsklad', 10);
}