<?php
/**
 * Main instance of Wsklad
 *
 * @return Wsklad\Core|boolean
 */
function wsklad()
{
	if(version_compare(PHP_VERSION, '5.6.0') < 0)
	{
		return false;
	}

	if(!is_callable('Wsklad\Core::instance'))
	{
		return false;
	}

	if(!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true))
	{
		return false;
	}

	return Wsklad\Core::instance();
}

/**
 * Use for plugin activation hook
 */
function wsklad_activation()
{
}

/**
 * Use for plugin deactivation hook
 */
function wsklad_deactivation()
{
}

/**
 * Main instance of Wsklad\Admin
 *
 * @return Wsklad\Admin|void
 */
function wsklad_admin()
{
	if(!is_callable('Wsklad\Admin::instance'))
	{
		return;
	}

	return Wsklad\Admin::instance();
}

/**
 * Localisation loading
 */
function wsklad_load_textdomain()
{
	/**
	 * WP 5.x or later
	 */
	if(function_exists('determine_locale'))
	{
		$locale = determine_locale();
	}
	else
	{
		$locale = is_admin() && function_exists('get_user_locale') ? get_user_locale() : get_locale();
	}

	$locale = apply_filters('plugin_locale', $locale, 'wsklad');

	unload_textdomain('wsklad');
	load_textdomain('wsklad', WP_LANG_DIR . '/plugins/wsklad-' . $locale . '.mo');
	load_textdomain('wsklad', WSKLAD_PLUGIN_PATH . 'languages/wsklad-' . $locale . '.mo');
}