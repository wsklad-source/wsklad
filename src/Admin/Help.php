<?php namespace Wsklad\Admin;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;

/**
 * Class Help
 *
 * @package Wsklad\Admin
 */
class Help
{
	use SingletonTrait;

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
				'id' => 'wsklad_help_tab',
				'title' => __( 'Help', 'wsklad' ),
				'content' => wsklad()->views()->getViewHtml('/helps/main.php')
			]
		);

		$screen->add_help_tab
		(
			[
				'id' => 'wsklad_bugs_tab',
				'title' => __( 'Found a bug?', 'wsklad' ),
				'content' => wsklad()->views()->getViewHtml('/helps/bugs.php')
			]
		);

		$screen->add_help_tab
		(
			[
				'id' => 'wsklad_features_tab',
				'title' => __( 'Not a feature?', 'wsklad' ),
				'content' => wsklad()->views()->getViewHtml('/helps/features.php')
			]
		);
	}
}