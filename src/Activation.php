<?php namespace Wsklad;

defined('ABSPATH') || exit;

/**
 * Activation
 *
 * @package Wsklad
 */
final class Activation extends \Digiom\Woplucore\Activation
{
	public function __construct()
	{
		if(false === get_option('wsklad_version', false))
		{
			update_option('wsklad_wizard', 'setup');

			wsklad()->admin()->notices()->create
			(
				[
					'id' => 'activation_welcome',
					'dismissible' => false,
					'type' => 'info',
					'data' => __('WSKLAD successfully activated. You have made the right choice to integrate the site with Moy Sklad (plugin number one)!', 'wsklad'),
					'extra_data' => sprintf
					(
						'<p>%s <a href="%s">%s</a></p>',
						__('The basic plugin setup has not been done yet, so you can proceed to the setup, which takes no more than 5 minutes.', 'wsklad'),
						admin_url('admin.php?page=wsklad'),
						__('Go to setting.', 'wsklad')
					)
				]
			);
		}

		if(false === get_option('wsklad_version_init', false))
		{
			update_option('wsklad_version_init', wsklad()->environment()->get('wsklad_version'));
		}
	}
}