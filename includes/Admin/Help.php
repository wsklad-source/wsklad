<?php
/**
 * Namespace
 */
namespace Wsklad\Admin;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Wsklad\Traits\Singleton;

/**
 * Class Help
 *
 * @package Wsklad\Admin
 */
class Help
{
	/**
	 * Traits
	 */
	use Singleton;

	/**
	 * Help constructor.
	 */
	public function __construct()
	{
		add_action('current_screen', [$this, 'add_tabs'], 50);
	}

	/**
	 * Add help tabs
	 */
	public function add_tabs()
	{
		$screen = get_current_screen();

		if(!$screen)
		{
			return;
		}

		$screen->add_help_tab
		(
			[
				'id' => WSKLAD_PREFIX . 'help_tab',
				'title' => __( 'Help', 'wsklad' ),
				'content' => wsklad_get_template_html('/helps/main.php')
			]
		);

		$screen->add_help_tab
		(
			[
				'id' => WSKLAD_PREFIX . 'bugs_tab',
				'title' => __( 'Found a bug?', 'wsklad' ),
				'content' => wsklad_get_template_html('/helps/bugs.php')
			]
		);

		$screen->add_help_tab
		(
			[
				'id' => WSKLAD_PREFIX . 'features_tab',
				'title' => __( 'Not a feature?', 'wsklad' ),
				'content' => wsklad_get_template_html('/helps/features.php')
			]
		);
	}
}