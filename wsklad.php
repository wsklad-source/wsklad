<?php
/**
 * Plugin Name: WSKLAD
 * Plugin URI: https://wsklad.ru
 * Description: Implementation of a mechanism for flexible exchange of various data between Moy Sklad and a site running WordPress.
 * Version: 0.1.0
 * Requires at least: 5.2
 * Requires PHP: 7.0
 * Text Domain: wsklad
 * Domain Path: /assets/languages
 * Copyright: WSKLAD team © 2019-2022
 * Author: WSKLAD team
 * Author URI: https://wsklad.ru
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Wsklad
 **/
defined('ABSPATH') || exit;

if(version_compare(PHP_VERSION, '7.0') < 0)
{
	return false;
}

if(false === defined('WSKLAD_PLUGIN_FILE'))
{
	define('WSKLAD_PLUGIN_FILE', __FILE__);

	/**
	 * Main instance of WSKLAD
	 *
	 * @return Wsklad\Core
	 */
	function wsklad(): Wsklad\Core
	{
		return Wsklad\Core::instance();
	}

	include_once __DIR__ . '/vendor/autoload.php';

	$loader = new Digiom\Woplucore\Loader();

	$loader->addNamespace('Wsklad', plugin_dir_path(WSKLAD_PLUGIN_FILE) . 'src');

	try
	{
		$loader->register(WSKLAD_PLUGIN_FILE);
	}
	catch(\Exception $e)
	{
		trigger_error($e->getMessage());
	}

	$loader->registerActivation([Wsklad\Activation::class, 'instance']);
	$loader->registerDeactivation([Wsklad\Deactivation::class, 'instance']);
	$loader->registerUninstall([Wsklad\Uninstall::class, 'instance']);

	wsklad()->register(new Wsklad\Context(), $loader);
}