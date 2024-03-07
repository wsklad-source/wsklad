<?php
/**
 * Plugin Name: WSKLAD
 * Plugin URI: https://wordpress.org/plugins/wsklad
 * Description: Implementation of a mechanism for flexible exchange of various data between Moy Sklad and a site running WordPress.
 * Version: 0.8.0
 * Requires at least: 5.2
 * Requires PHP: 7.0
 * Text Domain: wsklad
 * Domain Path: /assets/languages
 * Copyright: WSKLAD team © 2019-2024
 * Author: WSKLAD team
 * Author URI: https://wsklad.ru
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package WordPress\Plugins
 **/
namespace
{
	defined('ABSPATH') || exit;

    if(version_compare(PHP_VERSION, '7.0') < 0)
    {
        trigger_error('Minimal PHP version for used WC1C plugin: 7.0. Please update PHP version.');
        return false;
    }

	if(false === defined('WSKLAD_PLUGIN_FILE'))
	{
		define('WSKLAD_PLUGIN_FILE', __FILE__);

		$autoloader = __DIR__ . '/vendor/autoload.php';

		if(!is_readable($autoloader))
		{
			trigger_error('File is not found: ' . $autoloader);
			return false;
		}

		require_once $autoloader;

		/**
		 * For external use
		 *
		 * @return Wsklad\Core Main instance of core
		 */
		function wsklad(): Wsklad\Core
		{
			return Wsklad\Core();
		}
	}
}

/**
 * @package Wsklad
 */
namespace Wsklad
{
	/**
	 * For internal use
	 *
	 * @return Core Main instance of plugin core
	 */
	function core(): Core
	{
		return Core::instance();
	}

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

	$context = new Context(__FILE__, 'wsklad', $loader);

	core()->register($context);
}