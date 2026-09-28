<?php

/**
 * WordPress function stubs for the WSKLAD test suite.
 *
 * There is no WordPress installation on this machine and no WP test suite to
 * borrow, so every WordPress function the plugin touches is defined here. The
 * definitions are deliberately literal: `__()` returns the string it was given,
 * `esc_html()` escapes, `absint()` casts. A stub that quietly returned something
 * plausible but different from the real function would make the tests prove
 * nothing while looking green, which is worse than having no test at all.
 *
 * Two things are *not* stubbed as constants:
 *
 *  - `ABSPATH` is defined by `tests/bootstrap.php`, not here, so that loading
 *    this file twice cannot mask a missing definition.
 *  - The mutable state (options, hooks) lives in classes with a `reset()` method,
 *    never in a global a test cannot put back. A leaked option between two tests
 *    is the classic reason a suite passes on CI and fails on a laptop.
 *
 * @package Wsklad\Tests
 */

namespace Wsklad\Testing {

	/**
	 * Resettable WordPress state.
	 *
	 * Tests call `WordPress::reset()` in `setUp()`. That single call clears the
	 * option store, the site-option store, the hook registry, the fired-hook log
	 * and the recorded activation/deactivation hooks.
	 */
	final class WordPress
	{
		/**
		 * Blog options.
		 *
		 * @var array<string, mixed>
		 */
		public static $options = [];

		/**
		 * Network options.
		 *
		 * @var array<string, mixed>
		 */
		public static $site_options = [];

		/**
		 * Text domains passed to load_textdomain().
		 *
		 * @var string[]
		 */
		public static $textdomains = [];

		/**
		 * Activation and deactivation callbacks, in registration order.
		 *
		 * @var array<int, array{type: string, file: string, callback: callable}>
		 */
		public static $lifecycle_hooks = [];

		/**
		 * Answer to the next `is_admin()` call.
		 *
		 * @var bool
		 */
		public static $is_admin = false;

		/**
		 * Answer to the next `wp_doing_ajax()` call.
		 *
		 * @var bool
		 */
		public static $doing_ajax = false;

		/**
		 * Answer to the next `is_multisite()` call.
		 *
		 * @var bool
		 */
		public static $multisite = false;

		/**
		 * Capabilities the current user is granted.
		 *
		 * @var string[]
		 */
		public static $capabilities = ['manage_options', 'wsklad_manage_account'];

		/**
		 * Locale returned by determine_locale().
		 *
		 * @var string
		 */
		public static $locale = 'en_US';

		/**
		 * Reset everything to a clean, documented baseline.
		 *
		 * @return void
		 */
		public static function reset()
		{
			self::$options        = [];
			self::$site_options   = [];
			self::$textdomains    = [];
			self::$lifecycle_hooks = [];
			self::$is_admin       = false;
			self::$doing_ajax     = false;
			self::$multisite      = false;
			self::$capabilities   = ['manage_options', 'wsklad_manage_account'];
			self::$locale         = 'en_US';

			\Wsklad\Testing\Hooks::reset();
		}

		/**
		 * Seed the option store.
		 *
		 * @param array<string, mixed> $options
		 *
		 * @return void
		 */
		public static function setOptions(array $options)
		{
			self::$options = $options;
		}

		/**
		 * Seed the network option store.
		 *
		 * @param array<string, mixed> $options
		 *
		 * @return void
		 */
		public static function setSiteOptions(array $options)
		{
			self::$site_options = $options;
		}
	}
}

namespace {

	use Wsklad\Testing\Hooks;
	use Wsklad\Testing\WordPress;

	/* -------------------------------------------------------------------------
	 * Translation
	 * ---------------------------------------------------------------------- */

	if(!function_exists('__'))
	{
		/**
		 * Return the text unchanged.
		 *
		 * No catalogue is loaded, so this is the identity. The text domain is
		 * accepted and ignored: a test that wanted a translated string would need
		 * a real .mo file, and a fake one would test the fake.
		 *
		 * @param string $text
		 * @param string $domain
		 *
		 * @return string
		 */
		function __($text, $domain = 'default')
		{
			return (string) $text;
		}
	}

	if(!function_exists('_e'))
	{
		/**
		 * @param string $text
		 * @param string $domain
		 *
		 * @return void
		 */
		function _e($text, $domain = 'default')
		{
			echo __($text, $domain);
		}
	}

	if(!function_exists('esc_html__'))
	{
		/**
		 * @param string $text
		 * @param string $domain
		 *
		 * @return string
		 */
		function esc_html__($text, $domain = 'default')
		{
			return esc_html((string) $text);
		}
	}

	if(!function_exists('esc_attr__'))
	{
		/**
		 * @param string $text
		 * @param string $domain
		 *
		 * @return string
		 */
		function esc_attr__($text, $domain = 'default')
		{
			return esc_attr((string) $text);
		}
	}

	if(!function_exists('esc_html_e'))
	{
		/**
		 * @param string $text
		 * @param string $domain
		 *
		 * @return void
		 */
		function esc_html_e($text, $domain = 'default')
		{
			echo esc_html((string) $text);
		}
	}

	if(!function_exists('esc_attr_e'))
	{
		/**
		 * @param string $text
		 * @param string $domain
		 *
		 * @return void
		 */
		function esc_attr_e($text, $domain = 'default')
		{
			echo esc_attr((string) $text);
		}
	}

	if(!function_exists('_n'))
	{
		/**
		 * Plural form selection. There is no catalogue, so the singular always wins.
		 *
		 * @param string $single
		 * @param string $plural
		 * @param int    $number
		 * @param string $domain
		 *
		 * @return string
		 */
		function _n($single, $plural, $number, $domain = 'default')
		{
			return 1 === (int) $number ? (string) $single : (string) $plural;
		}
	}

	if(!function_exists('_x'))
	{
		/**
		 * Context selection. No catalogue, so the original string wins.
		 *
		 * @param string $text
		 * @param string $context
		 * @param string $domain
		 *
		 * @return string
		 */
		function _x($text, $context, $domain = 'default')
		{
			return (string) $text;
		}
	}

	if(!function_exists('load_textdomain'))
	{
		/**
		 * Record the attempt. Nothing is loaded.
		 *
		 * @param string $domain
		 * @param string $mofile
		 *
		 * @return bool
		 */
		function load_textdomain($domain, $mofile)
		{
			WordPress::$textdomains[] = (string) $domain;

			return true;
		}
	}

	if(!function_exists('determine_locale'))
	{
		/**
		 * @return string
		 */
		function determine_locale()
		{
			return WordPress::$locale;
		}
	}

	/* -------------------------------------------------------------------------
	 * Escaping and sanitising
	 * ---------------------------------------------------------------------- */

	if(!function_exists('esc_html'))
	{
		/**
		 * @param string $text
		 *
		 * @return string
		 */
		function esc_html($text)
		{
			return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
		}
	}

	if(!function_exists('esc_attr'))
	{
		/**
		 * @param string $text
		 *
		 * @return string
		 */
		function esc_attr($text)
		{
			return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
		}
	}

	if(!function_exists('esc_url'))
	{
		/**
		 * @param string $url
		 *
		 * @return string
		 */
		function esc_url($url)
		{
			$url = trim((string) $url);

			// Only the schemes a browser will not execute.
			if('' !== $url && !preg_match('#^(https?|mailto|tel|ftp)://#i', $url) && 0 !== strpos($url, 'mailto:') && 0 !== strpos($url, 'tel:'))
			{
				return '';
			}

			return str_replace(['"', "'", '<', '>'], ['&quot;', '&#039;', '&lt;', '&gt;'], $url);
		}
	}

	if(!function_exists('esc_sql'))
	{
		/**
		 * Escape for a SQL string literal.
		 *
		 * @param string $text
		 *
		 * @return string
		 */
		function esc_sql($text)
		{
			return addslashes((string) $text);
		}
	}

	if(!function_exists('sanitize_key'))
	{
		/**
		 * @param string $key
		 *
		 * @return string
		 */
		function sanitize_key($key)
		{
			return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $key));
		}
	}

	if(!function_exists('sanitize_text_field'))
	{
		/**
		 * @param string $text
		 *
		 * @return string
		 */
		function sanitize_text_field($text)
		{
			$text = wp_strip_all_tags((string) $text);

			return trim(preg_replace('/[\r\n\t ]+/', ' ', $text));
		}
	}

	if(!function_exists('wp_strip_all_tags'))
	{
		/**
		 * @param string $text
		 * @param bool   $remove_breaks
		 *
		 * @return string
		 */
		function wp_strip_all_tags($text, $remove_breaks = false)
		{
			$text = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $text);
			$text = strip_tags((string) $text);

			return $remove_breaks ? trim(preg_replace('/[\r\n\t ]+/', ' ', $text)) : $text;
		}
	}

	if(!function_exists('absint'))
	{
		/**
		 * @param mixed $value
		 *
		 * @return int
		 */
		function absint($value)
		{
			return abs((int) $value);
		}
	}

	if(!function_exists('wp_unslash'))
	{
		/**
		 * @param mixed $value
		 *
		 * @return mixed
		 */
		function wp_unslash($value)
		{
			if(is_array($value))
			{
				return array_map('wp_unslash', $value);
			}

			return is_string($value) ? stripslashes($value) : $value;
		}
	}

	if(!function_exists('wp_slash'))
	{
		/**
		 * @param mixed $value
		 *
		 * @return mixed
		 */
		function wp_slash($value)
		{
			if(is_array($value))
			{
				return array_map('wp_slash', $value);
			}

			return is_string($value) ? addslashes($value) : $value;
		}
	}

	if(!function_exists('wp_json_encode'))
	{
		/**
		 * @param mixed $data
		 * @param int   $options
		 * @param int   $depth
		 *
		 * @return string|false
		 */
		function wp_json_encode($data, $options = 0, $depth = 512)
		{
			return json_encode($data, $options, $depth);
		}
	}

	if(!function_exists('maybe_serialize'))
	{
		/**
		 * @param mixed $data
		 *
		 * @return mixed
		 */
		function maybe_serialize($data)
		{
			if(is_array($data) || is_object($data))
			{
				return serialize($data);
			}

			return $data;
		}
	}

	if(!function_exists('maybe_unserialize'))
	{
		/**
		 * @param mixed $data
		 *
		 * @return mixed
		 */
		function maybe_unserialize($data)
		{
			if(is_string($data) && preg_match('/^([adObis]:|N;)/', $data))
			{
				// `allowed_classes => false`: a stub must not be the thing that
				// instantiates an object in a security test.
				return @unserialize(trim($data), ['allowed_classes' => false]);
			}

			return $data;
		}
	}

	if(!function_exists('wp_parse_args'))
	{
		/**
		 * @param array|object|string $args
		 * @param array              $defaults
		 *
		 * @return array
		 */
		function wp_parse_args($args, $defaults = [])
		{
			if(is_object($args))
			{
				$parsed = get_object_vars($args);
			}
			elseif(is_array($args))
			{
				$parsed = $args;
			}
			else
			{
				parse_str((string) $args, $parsed);
			}

			return is_array($defaults) ? array_merge($defaults, $parsed) : $parsed;
		}
	}

	if(!function_exists('number_format_i18n'))
	{
		/**
		 * @param float $number
		 * @param int   $decimals
		 *
		 * @return string
		 */
		function number_format_i18n($number, $decimals = 0)
		{
			return number_format((float) $number, (int) $decimals);
		}
	}

	/* -------------------------------------------------------------------------
	 * Hooks
	 * ---------------------------------------------------------------------- */

	if(!function_exists('add_action'))
	{
		/**
		 * @param string   $hook
		 * @param callable $callback
		 * @param int      $priority
		 * @param int      $accepted_args
		 *
		 * @return bool
		 */
		function add_action($hook, $callback, $priority = 10, $accepted_args = 1)
		{
			return Hooks::add((string) $hook, $callback, (int) $priority, (int) $accepted_args);
		}
	}

	if(!function_exists('add_filter'))
	{
		/**
		 * @param string   $hook
		 * @param callable $callback
		 * @param int      $priority
		 * @param int      $accepted_args
		 *
		 * @return bool
		 */
		function add_filter($hook, $callback, $priority = 10, $accepted_args = 1)
		{
			return Hooks::add((string) $hook, $callback, (int) $priority, (int) $accepted_args);
		}
	}

	if(!function_exists('do_action'))
	{
		/**
		 * @param string $hook
		 * @param mixed  ...$args
		 *
		 * @return void
		 */
		function do_action($hook, ...$args)
		{
			Hooks::action((string) $hook, $args);
		}
	}

	if(!function_exists('apply_filters'))
	{
		/**
		 * @param string $hook
		 * @param mixed  $value
		 * @param mixed  ...$args
		 *
		 * @return mixed
		 */
		function apply_filters($hook, $value, ...$args)
		{
			return Hooks::filter((string) $hook, $value, $args);
		}
	}

	if(!function_exists('has_action'))
	{
		/**
		 * @param string        $hook
		 * @param callable|bool $callback
		 *
		 * @return bool|int
		 */
		function has_action($hook, $callback = false)
		{
			return Hooks::has((string) $hook, $callback);
		}
	}

	if(!function_exists('has_filter'))
	{
		/**
		 * @param string        $hook
		 * @param callable|bool $callback
		 *
		 * @return bool|int
		 */
		function has_filter($hook, $callback = false)
		{
			return Hooks::has((string) $hook, $callback);
		}
	}

	if(!function_exists('remove_action'))
	{
		/**
		 * @param string   $hook
		 * @param callable $callback
		 * @param int      $priority
		 *
		 * @return bool
		 */
		function remove_action($hook, $callback, $priority = 10)
		{
			return Hooks::remove((string) $hook, $callback, (int) $priority);
		}
	}

	if(!function_exists('remove_filter'))
	{
		/**
		 * @param string   $hook
		 * @param callable $callback
		 * @param int      $priority
		 *
		 * @return bool
		 */
		function remove_filter($hook, $callback, $priority = 10)
		{
			return Hooks::remove((string) $hook, $callback, (int) $priority);
		}
	}

	/* -------------------------------------------------------------------------
	 * Options
	 * ---------------------------------------------------------------------- */

	if(!function_exists('get_option'))
	{
		/**
		 * @param string $option
		 * @param mixed  $default
		 *
		 * @return mixed
		 */
		function get_option($option, $default = false)
		{
			return array_key_exists($option, WordPress::$options) ? WordPress::$options[$option] : $default;
		}
	}

	if(!function_exists('add_option'))
	{
		/**
		 * @param string $option
		 * @param mixed  $value
		 *
		 * @return bool
		 */
		function add_option($option, $value = '')
		{
			if(array_key_exists($option, WordPress::$options))
			{
				return false;
			}

			WordPress::$options[$option] = $value;

			return true;
		}
	}

	if(!function_exists('update_option'))
	{
		/**
		 * @param string $option
		 * @param mixed  $value
		 *
		 * @return bool
		 */
		function update_option($option, $value)
		{
			WordPress::$options[$option] = $value;

			return true;
		}
	}

	if(!function_exists('delete_option'))
	{
		/**
		 * @param string $option
		 *
		 * @return bool
		 */
		function delete_option($option)
		{
			if(!array_key_exists($option, WordPress::$options))
			{
				return false;
			}

			unset(WordPress::$options[$option]);

			return true;
		}
	}

	if(!function_exists('get_site_option'))
	{
		/**
		 * @param string $option
		 * @param mixed  $default
		 *
		 * @return mixed
		 */
		function get_site_option($option, $default = false)
		{
			return array_key_exists($option, WordPress::$site_options) ? WordPress::$site_options[$option] : $default;
		}
	}

	if(!function_exists('add_site_option'))
	{
		/**
		 * @param string $option
		 * @param mixed  $value
		 *
		 * @return bool
		 */
		function add_site_option($option, $value)
		{
			if(array_key_exists($option, WordPress::$site_options))
			{
				return false;
			}

			WordPress::$site_options[$option] = $value;

			return true;
		}
	}

	if(!function_exists('update_site_option'))
	{
		/**
		 * @param string $option
		 * @param mixed  $value
		 *
		 * @return bool
		 */
		function update_site_option($option, $value)
		{
			WordPress::$site_options[$option] = $value;

			return true;
		}
	}

	if(!function_exists('delete_site_option'))
	{
		/**
		 * @param string $option
		 *
		 * @return bool
		 */
		function delete_site_option($option)
		{
			if(!array_key_exists($option, WordPress::$site_options))
			{
				return false;
			}

			unset(WordPress::$site_options[$option]);

			return true;
		}
	}

	/* -------------------------------------------------------------------------
	 * Request context
	 * ---------------------------------------------------------------------- */

	if(!function_exists('is_admin'))
	{
		/**
		 * @return bool
		 */
		function is_admin()
		{
			return (bool) WordPress::$is_admin;
		}
	}

	if(!function_exists('wp_doing_ajax'))
	{
		/**
		 * @return bool
		 */
		function wp_doing_ajax()
		{
			return (bool) WordPress::$doing_ajax;
		}
	}

	if(!function_exists('wp_doing_cron'))
	{
		/**
		 * @return bool
		 */
		function wp_doing_cron()
		{
			return false;
		}
	}

	if(!function_exists('is_multisite'))
	{
		/**
		 * @return bool
		 */
		function is_multisite()
		{
			return (bool) WordPress::$multisite;
		}
	}

	if(!function_exists('get_current_blog_id'))
	{
		/**
		 * @return int
		 */
		function get_current_blog_id()
		{
			return WordPress::$multisite ? 2 : 1;
		}
	}

	if(!function_exists('get_current_user_id'))
	{
		/**
		 * @return int
		 */
		function get_current_user_id()
		{
			return 1;
		}
	}

	if(!function_exists('current_user_can'))
	{
		/**
		 * @param string $capability
		 *
		 * @return bool
		 */
		function current_user_can($capability)
		{
			return in_array((string) $capability, WordPress::$capabilities, true);
		}
	}

	if(!function_exists('add_query_arg'))
	{
		/**
		 * @param array|string $key
		 * @param mixed        $value
		 * @param string       $url
		 *
		 * @return string
		 */
		function add_query_arg($key, $value = null, $url = null)
		{
			if(is_array($key))
			{
				$pairs = $key;
			}
			elseif(!is_null($url))
			{
				$pairs = [$key => $value];
			}
			else
			{
				return (string) $key;
			}

			$separator = false === strpos((string) $url, '?') ? '?' : '&';

			return (string) $url . $separator . http_build_query($pairs);
		}
	}

	if(!function_exists('admin_url'))
	{
		/**
		 * @param string $path
		 *
		 * @return string
		 */
		function admin_url($path = '')
		{
			return 'https://example.test/wp-admin/' . ltrim((string) $path, '/');
		}
	}

	if(!function_exists('home_url'))
	{
		/**
		 * @param string $path
		 *
		 * @return string
		 */
		function home_url($path = '')
		{
			return 'https://example.test/' . ltrim((string) $path, '/');
		}
	}

	if(!function_exists('plugin_dir_path'))
	{
		/**
		 * @param string $file
		 *
		 * @return string
		 */
		function plugin_dir_path($file)
		{
			return rtrim(str_replace('\\', '/', dirname((string) $file)), '/') . '/';
		}
	}

	if(!function_exists('plugin_dir_url'))
	{
		/**
		 * @param string $file
		 *
		 * @return string
		 */
		function plugin_dir_url($file)
		{
			return 'https://example.test/wp-content/plugins/wsklad/';
		}
	}

	if(!function_exists('plugin_basename'))
	{
		/**
		 * @param string $file
		 *
		 * @return string
		 */
		function plugin_basename($file)
		{
			$file = str_replace('\\', '/', (string) $file);

			return 'wsklad/' . basename($file);
		}
	}

	/* -------------------------------------------------------------------------
	 * Nonces
	 * ---------------------------------------------------------------------- */

	if(!function_exists('wp_create_nonce'))
	{
		/**
		 * Deterministic, so a test can assert on it.
		 *
		 * @param string $action
		 *
		 * @return string
		 */
		function wp_create_nonce($action = -1)
		{
			return substr(md5('nonce:' . (string) $action), 0, 10);
		}
	}

	if(!function_exists('wp_verify_nonce'))
	{
		/**
		 * A nonce is valid when it is the one this stub would have issued.
		 *
		 * @param string $nonce
		 * @param string $action
		 *
		 * @return int|false
		 */
		function wp_verify_nonce($nonce, $action = -1)
		{
			return hash_equals(wp_create_nonce($action), (string) $nonce) ? 1 : false;
		}
	}

	if(!function_exists('wp_nonce_url'))
	{
		/**
		 * @param string $url
		 * @param string $action
		 * @param string $name
		 *
		 * @return string
		 */
		function wp_nonce_url($url, $action = -1, $name = '_wpnonce')
		{
			return add_query_arg($name, wp_create_nonce($action), $url);
		}
	}

	/* -------------------------------------------------------------------------
	 * Randomness, filesystem, cron
	 * ---------------------------------------------------------------------- */

	if(!function_exists('wp_rand'))
	{
		/**
		 * @param int $min
		 * @param int $max
		 *
		 * @return int
		 */
		function wp_rand($min = 0, $max = 0)
		{
			return random_int((int) $min, 0 === (int) $max ? PHP_INT_MAX : (int) $max);
		}
	}

	if(!function_exists('wp_generate_password'))
	{
		/**
		 * @param int  $length
		 * @param bool $special_chars
		 * @param bool $extra_special_chars
		 *
		 * @return string
		 */
		function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false)
		{
			$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

			if($special_chars)
			{
				$alphabet .= '!@#$%^&*()';
			}

			$password = '';

			for($i = 0; $i < (int) $length; $i++)
			{
				$password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
			}

			return $password;
		}
	}

	if(!function_exists('wp_mkdir_p'))
	{
		/**
		 * @param string $target
		 *
		 * @return bool
		 */
		function wp_mkdir_p($target)
		{
			return is_dir((string) $target) || mkdir((string) $target, 0777, true);
		}
	}

	if(!function_exists('wp_next_scheduled'))
	{
		/**
		 * @param string $event
		 *
		 * @return int|false
		 */
		function wp_next_scheduled($event)
		{
			return false;
		}
	}

	if(!function_exists('wp_unschedule_event'))
	{
		/**
		 * @param int $timestamp
		 *
		 * @return bool
		 */
		function wp_unschedule_event($timestamp)
		{
			return true;
		}
	}

	if(!function_exists('dbDelta'))
	{
		/**
		 * @param string $queries
		 *
		 * @return array
		 */
		function dbDelta($queries)
		{
			return [];
		}
	}

	if(!function_exists('is_wp_error'))
	{
		/**
		 * @param mixed $thing
		 *
		 * @return bool
		 */
		function is_wp_error($thing)
		{
			return $thing instanceof \WP_Error;
		}
	}

	/* -------------------------------------------------------------------------
	 * Errors and constants
	 * ---------------------------------------------------------------------- */

	if(!class_exists('WP_Error'))
	{
		/**
		 * Minimal WP_Error, sufficient for `new WP_Error(…)` and `is_wp_error()`.
		 */
		class WP_Error
		{
			/**
			 * @var string
			 */
			public $code;

			/**
			 * @var string
			 */
			public $message;

			/**
			 * @var mixed
			 */
			public $data;

			/**
			 * @param string $code
			 * @param string $message
			 * @param mixed  $data
			 */
			public function __construct($code = '', $message = '', $data = '')
			{
				$this->code    = (string) $code;
				$this->message = (string) $message;
				$this->data    = $data;
			}

			/**
			 * @param string $code
			 *
			 * @return string
			 */
			public function get_error_code()
			{
				return $this->code;
			}

			/**
			 * @return string
			 */
			public function get_error_message()
			{
				return $this->message;
			}
		}
	}

	if(!defined('OBJECT'))
	{
		define('OBJECT', 'OBJECT');
	}

	if(!defined('ARRAY_A'))
	{
		define('ARRAY_A', 'ARRAY_A');
	}

	if(!defined('ARRAY_N'))
	{
		define('ARRAY_N', 'ARRAY_N');
	}
}
