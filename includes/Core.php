<?php
/**
 * Namespace
 */
namespace Wsklad;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Wsklad\Traits\Singleton;
use Wsklad\Admin;

/**
 * Class Core
 *
 * @package Wsklad
 */
class Core
{
	/**
	 * Traits
	 */
	use Singleton;

	/**
	 * Core constructor
	 */
	public function __construct()
	{
		// hook
		do_action(WSKLAD_PREFIX . 'before_loading');

		// localization files
		wsklad_load_textdomain();

		// init
		add_action('init', [$this, 'init'], 3);

		// admin
		if(false !== is_admin())
		{
			add_action('init', [Admin::class, 'instance'], 5);
		}

		// hook
		do_action(WSKLAD_PREFIX . 'after_loading');
	}

	/**
	 * Initialization
	 */
	public function init()
	{
		// hook
		do_action(WSKLAD_PREFIX . 'before_init');



		// hook
		do_action(WSKLAD_PREFIX . 'after_init');
	}
}