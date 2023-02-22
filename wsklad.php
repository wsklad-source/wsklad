<?php
/**
 * Plugin Name: WSKLAD
 * Plugin URI: https://wordpress.org/plugins/wsklad
 * Description: Implementation of a mechanism for flexible exchange of various data between Moy Sklad and a site running WordPress.
 * Version: 0.2.0
 * Requires at least: 5.2
 * Requires PHP: 7.0
 * Text Domain: wsklad
 * Domain Path: /assets/languages
 * Copyright: WSKLAD team © 2019-2023
 * Author: WSKLAD team
 * Author URI: https://wsklad.ru
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Wsklad
 **/
namespace
{
	defined('ABSPATH') || exit;

	if(version_compare(PHP_VERSION, '7.0') < 0)
	{
		return false;
	}

	if(false === defined('WSKLAD_PLUGIN_FILE'))
	{
		define('WSKLAD_PLUGIN_FILE', __FILE__);

		include_once __DIR__ . '/vendor/autoload.php';

		/**
		 * Main instance of WSKLAD
		 *
		 * @return Wsklad\Core
		 */
		function wsklad(): Wsklad\Core
		{
			return Wsklad\Core::instance();
		}
	}
}

/**
 * @package Wsklad
 */
namespace Wsklad
{
	$loader = new \Digiom\Woplucore\Loader();

	try
	{
		$loader->addNamespace(__NAMESPACE__, plugin_dir_path(__FILE__) . 'src');

		$loader->register(__FILE__);

		$loader->registerActivation([Activation::class, 'instance']);
		$loader->registerDeactivation([Deactivation::class, 'instance']);
		$loader->registerUninstall([Uninstall::class, 'instance']);
	}
	catch(\Throwable $e)
	{
		trigger_error($e->getMessage());
		return false;
	}

	wsklad()->register(new Context(), $loader);
}