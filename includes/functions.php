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

/**
 * Is Wsklad admin request?
 *
 * @return bool
 */
function is_wsklad_admin_request()
{
	if(false !== is_admin() && 'wsklad' === wsklad_get_var($_GET['page'], ''))
	{
		return true;
	}

	return false;
}

/**
 * Get data if set, otherwise return a default value or null
 * Prevents notices when data is not set
 *
 * @param mixed $var variable
 * @param string $default default value
 *
 * @return mixed
 */
function wsklad_get_var(&$var, $default = null)
{
	return isset($var) ? $var : $default;
}

/**
 * Pretty debug
 *
 * @param $data
 * @param bool $die
 */
function wsklad_debug($data, $die = true)
{
	echo "<pre>";
	var_dump($data);
	echo "</pre>";

	if($die)
	{
		die;
	}
}

/**
 * Is Wsklad admin section request?
 *
 * @param string $section
 *
 * @return bool
 */
function is_wsklad_admin_section_request($section = '')
{
	if('' === $section)
	{
		return false;
	}

	if(is_wsklad_admin_request() && wsklad_get_var($_GET['section'], '') === $section)
	{
		return true;
	}

	return false;
}

/**
 * Outputs a "back" link so admin screens can easily jump back a page
 *
 * @param string $label title of the page to return to.
 * @param string $url URL of the page to return to.
 */
function wsklad_admin_back_link($label, $url)
{
	echo '<h2 style="margin-bottom: 20px;margin-top: 15px;">' . esc_attr($label) . '<small class="wc-admin-breadcrumb" style="margin-left: 10px;"><a href="' . esc_url($url) . '" aria-label="' . esc_attr($label) . '"> &#x2934;</a></small></h2>';
}