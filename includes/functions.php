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
 * Use for DB queries
 *
 * @return wpdb
 */
function wsklad_wpdb()
{
	global $wpdb;

	return $wpdb;
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

/**
 * Get templates
 *
 * @param string $template_name template name
 * @param array $args arguments (default: array)
 * @param string $template_path template path (default: '')
 * @param string $default_path default path (default: '')
 */
function wsklad_get_template($template_name, $args = [], $template_path = '', $default_path = '')
{
	$located = wsklad_locate_template($template_name, $template_path, $default_path);

	if(!file_exists($located))
	{
		return;
	}

	$located = apply_filters(WSKLAD_PREFIX . 'get_template', $located, $template_name, $args, $template_path, $default_path);

	do_action(WSKLAD_PREFIX . 'get_template_before', $template_name, $template_path, $located, $args);

	include $located;

	do_action(WSKLAD_PREFIX . 'get_template_after', $template_name, $template_path, $located, $args);
}

/**
 * Get template part
 *
 * @param mixed $slug Template slug
 * @param string $name Template name (default: '')
 */
function wsklad_get_template_part($slug, $name = '')
{
	$template = '';

	// Look in yourtheme/wsklad/slug-name.php
	if($name)
	{
		$template = locate_template(['wsklad/' . "{$slug}-{$name}.php"]);
	}

	// Get default slug-name.php
	if(!$template && $name && file_exists(WSKLAD_PLUGIN_PATH . "templates/{$slug}-{$name}.php"))
	{
		$template = WSKLAD_PLUGIN_PATH . "templates/{$slug}-{$name}.php";
	}

	// If template file doesn't exist, look in yourtheme/wsklad/slug.php
	if(!$template)
	{
		$template = locate_template(['wsklad/' . "{$slug}.php"]);
	}

	// Allow 3rd party plugins to filter template file from their plugin
	$template = apply_filters(WSKLAD_PREFIX . 'get_template_part', $template, $slug, $name);

	if($template)
	{
		load_template($template, false);
	}
}

/**
 * Like wsklad_get_template, but returns the HTML instead of outputting
 *
 * @param string $template_name template name
 * @param array $args arguments (default: array)
 * @param string $template_path template path (default: '')
 * @param string $default_path default path (default: '')
 *
 * @return string
 */
function wsklad_get_template_html($template_name, $args = [], $template_path = '', $default_path = '')
{
	ob_start();
	wsklad_get_template($template_name, $args, $template_path, $default_path);

	return ob_get_clean();
}

/**
 * Locate a template and return the path for inclusion
 *
 * This is the load order:
 * yourtheme/wsklad/$template_name
 * $default_path/$template_name
 *
 * @param string $template_name template name
 * @param string $template_path template path (default: '')
 * @param string $default_path default path (default: '')
 *
 * @return string
 */
function wsklad_locate_template($template_name, $template_path = '', $default_path = '')
{
	$template = false;

	if(!$template_path)
	{
		$template_path = 'wsklad';
	}

	if(!$default_path)
	{
		$default_path = WSKLAD_PLUGIN_PATH . 'templates/';
	}

	if($template_path && file_exists(trailingslashit($template_path) . $template_name))
	{
		$template = trailingslashit($template_path) . $template_name;
	}

	// Get default template/
	if(!$template)
	{
		$template = $default_path . $template_name;
	}

	// Return what we found
	return apply_filters(WSKLAD_PREFIX . 'locate_template', $template, $template_name, $template_path);
}

/**
 * Get admin accounts URL
 *
 * @param string $action
 * @param string $account_id
 *
 * @return string
 */
function wsklad_admin_accounts_get_url($action = 'list', $account_id = '')
{
	$path = 'admin.php?page=wsklad&section=accounts';

	if('list' !== $action)
	{
		$path .= '&do_action=' . $action;
	}

	if('' === $account_id)
	{
		return admin_url($path);
	}

	$path .= '&account_id=' . $account_id;

	return admin_url($path);
}

/**
 * Convert mysql datetime to PHP timestamp, forcing UTC. Wrapper for strtotime
 *
 * @param string $time_string Time string
 * @param int|null $from_timestamp Timestamp to convert from
 *
 * @return int
 */
function wsklad_string_to_timestamp($time_string, $from_timestamp = null)
{
	$original_timezone = date_default_timezone_get();

	date_default_timezone_set('UTC');

	if(null === $from_timestamp)
	{
		$next_timestamp = strtotime($time_string);
	}
	else
	{
		$next_timestamp = strtotime($time_string, $from_timestamp);
	}

	date_default_timezone_set($original_timezone);

	return $next_timestamp;
}

/**
 * Helper to retrieve the timezone string for a site until
 *
 * @return string PHP timezone string for the site
 */
function wsklad_timezone_string()
{
	// If site timezone string exists, return it
	$timezone = get_option('timezone_string');

	if($timezone)
	{
		return $timezone;
	}

	// Get UTC offset, if it isn't set then return UTC
	$utc_offset = (int) get_option('gmt_offset', 0);
	if(0 === $utc_offset)
	{
		return 'UTC';
	}

	// Adjust UTC offset from hours to seconds
	$utc_offset *= 3600;

	// Attempt to guess the timezone string from the UTC offset
	$timezone = timezone_name_from_abbr('', $utc_offset);
	if($timezone)
	{
		return $timezone;
	}

	// Last try, guess timezone string manually
	foreach(timezone_abbreviations_list() as $abbr)
	{
		foreach($abbr as $city)
		{
			// WordPress restrict the use of date(), since it's affected by timezone settings, but in this case is just what we need to guess the correct timezone
			if((bool) date('I') === (bool) $city['dst'] && $city['timezone_id'] && (int) $city['offset'] === $utc_offset)
			{
				return $city['timezone_id'];
			}
		}
	}

	return 'UTC';
}

/**
 * Get timezone offset in seconds
 *
 * @return float
 */
function wsklad_timezone_offset()
{
	$timezone = get_option('timezone_string');

	if($timezone)
	{
		$timezone_object = new \DateTimeZone($timezone);

		return $timezone_object->getOffset(new DateTime('now'));
	}

	return (float) get_option('gmt_offset', 0) * HOUR_IN_SECONDS;
}