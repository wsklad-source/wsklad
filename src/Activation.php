<?php namespace Wsklad;

defined('ABSPATH') || exit;

/**
 * Activation
 *
 * Before 0.10.1 the database was created by the setup wizard, so every install path that
 * skipped the wizard left the site without tables. Schema creation now happens here, and
 * `Core::ensureSchema()` re-creates them if they ever disappear again.
 *
 * @package Wsklad
 */
final class Activation extends \Digiom\Woplucore\Activation
{
	/**
	 * Per-blog activation. The framework calls this for every blog on a network.
	 */
	public function __construct()
	{
		$this->createDirectories();
		$this->createSchema();
		$this->createKeyMaterial();
		$this->createNotices();

		if(false === get_option('wsklad_version_init', false))
		{
			update_option('wsklad_version_init', wsklad()->environment()->get('wsklad_version'));
		}
	}

	/**
	 * Create the private data directory and drop protective files.
	 *
	 * @return void
	 */
	private function createDirectories()
	{
		try
		{
			wsklad()->environment()->protectDirectories();
		}
		catch(\Throwable $e)
		{
			// A host without a writable wp-content must not abort activation; the admin
			// notice in Admin::cryptographyNotice()/Core::ensureDirectories() covers it.
			update_option('wsklad_directories_error', $e->getMessage(), false);
		}
	}

	/**
	 * Install the database tables.
	 *
	 * @return void
	 */
	private function createSchema()
	{
		try
		{
			wsklad()->schema()->install();
		}
		catch(\Throwable $e)
		{
			update_option('wsklad_schema_error', $e->getMessage(), false);
		}
	}

	/**
	 * Generate the per-install encryption salt and the initial key id.
	 *
	 * @return void
	 */
	private function createKeyMaterial()
	{
		$provider = wsklad()->keyProvider();

		$provider->getSalt();
		$provider->getKeyId();
	}

	/**
	 * Welcome notice, only on a genuinely fresh install.
	 *
	 * @return void
	 */
	private function createNotices()
	{
		if(false !== get_option('wsklad_version', false))
		{
			return;
		}

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
}
