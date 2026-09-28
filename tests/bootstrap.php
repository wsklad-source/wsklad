<?php

/**
 * PHPUnit bootstrap for the WSKLAD test suite.
 *
 * Load order matters and is deliberate:
 *
 *   1. `ABSPATH` — every file in `src/` and `views/` opens with
 *      `defined('ABSPATH') || exit;`. Autoloading one of those files without it
 *      kills the PHP process, and the failure looks like a segfault in
 *      `vendor/`, not like a missing constant. So it goes first, before the
 *      autoloader, before everything.
 *   2. `vendor/autoload.php` — the plugin's own `Wsklad\` PSR-4 mapping.
 *   3. The stub layer — WordPress functions, the hook registry, the fake `$wpdb`.
 *
 * The plugin cannot be fully exercised here: it needs a WordPress request, a
 * database and a browser. What it *can* be subjected to is its own source, and
 * that is what the contract tests do — they read `src/` and `views/` and assert
 * on what they find, which needs no WordPress at all.
 *
 * @package Wsklad\Tests
 */

if(!defined('ABSPATH'))
{
	// Not a real WordPress root: a path that exists, so that any code doing
	// `ABSPATH . 'wp-includes/…'` fails on the file, not on the constant.
	define('ABSPATH', dirname(__DIR__) . '/');
}

if(!defined('WSKLAD_TESTS_DIR'))
{
	define('WSKLAD_TESTS_DIR', __DIR__ . '/');
}

if(!defined('WSKLAD_ROOT_DIR'))
{
	define('WSKLAD_ROOT_DIR', dirname(__DIR__) . '/');
}

$wsklad_autoload = WSKLAD_ROOT_DIR . 'vendor/autoload.php';

if(!is_file($wsklad_autoload))
{
	fwrite
	(
		STDERR,
		"WSKLAD test bootstrap: vendor/autoload.php not found at {$wsklad_autoload}.\n"
		. "Run `composer install` before running the test suite.\n"
	);

	exit(1);
}

require_once $wsklad_autoload;

/*
 * The stub layer. Order is hooks -> wordpress -> wpdb:
 * `Wsklad\Testing\WordPress::reset()` calls `Hooks::reset()`, and the fake
 * `$wpdb` refers to the `OBJECT` constant that `wordpress.php` defines.
 */
require_once WSKLAD_TESTS_DIR . 'stubs/hooks.php';
require_once WSKLAD_TESTS_DIR . 'stubs/wordpress.php';
require_once WSKLAD_TESTS_DIR . 'stubs/wpdb.php';
require_once WSKLAD_TESTS_DIR . 'stubs/scanner.php';
