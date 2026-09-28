<?php namespace Wsklad;

defined('ABSPATH') || exit;

/**
 * Uninstall
 *
 * Soft by default. A full wipe requires explicit consent, because the plugin stores
 * Moy Sklad credentials and account mappings that cannot be reconstructed from anything
 * else — a user who clicks "Delete" in WordPress and then reinstalls has not lost a
 * cache, they have lost their configuration.
 *
 * Consent is `wsklad_uninstall_remove_data=yes` in `wp_options`, or a filter returning
 * true, or the `WSKLAD_UNINSTALL_REMOVE_DATA` constant in wp-config.php.
 *
 * @package Wsklad
 */
final class Uninstall extends \Digiom\Woplucore\Uninstall
{
	/**
	 * Option that must equal 'yes' for a full wipe.
	 */
	const CONSENT_OPTION = 'wsklad_uninstall_remove_data';

	/**
	 * Tables removed on a full uninstall.
	 *
	 * The two the 0.10.1 schema creates. The queue, credentials and mappings tables arrive
	 * with 0.12.0 and are added to this list in that release — a full uninstall must never
	 * leave a table behind, so the list and `Schema::getTables()` are asserted to agree in
	 * both directions by the integration suite.
	 *
	 * @var array
	 */
	private $tables =
	[
		'wsklad_accounts',
		'wsklad_accounts_meta',
	];

	/**
	 * Options removed on a full uninstall.
	 *
	 * ⚠ `wsklad_install_salt` and `wsklad_key_id` are network options and are listed in
	 * `getNetworkOptions()`; they must go too, or the next install derives the same key
	 * from a salt the previous user believes is gone.
	 *
	 * @var array
	 */
	private $options =
	[
		'wsklad_version',
		'wsklad_version_init',
		'wsklad_version_active',
		'wsklad_version_database',
		'wsklad_schema_version',
		'wsklad_migrations',
		'wsklad_migrations_failed',
		'wsklad_wizard',
		'wsklad_settings_main',
		'wsklad_settings_logs',
		'wsklad_settings_interface',
		'wsklad_admin_notices',
		'wsklad_directories_checked',
		'wsklad_schema_checked',
		'wsklad_capabilities_checked',
		'wsklad_update_checked',
		'wsklad_directories_error',
		'wsklad_schema_error',
		'wsklad_capabilities_error',
		'wsklad_uninstall_remove_data',
	];

	public function __construct()
	{
		$remove = $this->hasConsent();

		/**
		 * Allow a site to force a full wipe from code, e.g. a teardown script.
		 *
		 * @param bool $remove
		 */
		$remove = apply_filters('wsklad_uninstall_remove_data', $remove);

		if(!$remove)
		{
			// Soft uninstall: scheduled events go, data stays. Reactivating must be a
			// one-click operation, not a reconfiguration project.
			$this->clearSchedule();

			return;
		}

		$this->clearSchedule();
		$this->dropTables();
		$this->deleteOptions();
		$this->deleteFiles();

		do_action('wsklad_uninstall_complete');
	}

	/**
	 * Whether the user asked for a full wipe.
	 *
	 * @return bool
	 */
	public function hasConsent(): bool
	{
		if(defined('WSKLAD_UNINSTALL_REMOVE_DATA') && WSKLAD_UNINSTALL_REMOVE_DATA)
		{
			return true;
		}

		return 'yes' === get_option(self::CONSENT_OPTION, 'no');
	}

	/**
	 * Remove the plugin's scheduled events.
	 *
	 * @return void
	 */
	private function clearSchedule()
	{
		if(!function_exists('wp_clear_scheduled_hook'))
		{
			return;
		}

		$events =
		[
			'wsklad_cron',
			'wsklad_async_request',
			'wsklad_logs_cleanup',
			'wsklad_update',
		];

		foreach($events as $event)
		{
			wp_clear_scheduled_hook($event);

			while(function_exists('wp_next_scheduled') && wp_next_scheduled($event))
			{
				wp_unschedule_event(wp_next_scheduled($event));
			}
		}
	}

	/**
	 * Drop every table the plugin owns.
	 *
	 * ⚠ Every table is verified afterwards, not just issued a `DROP TABLE IF EXISTS`.
	 *
	 * `IF EXISTS` does not fail when a table is *absent*, which is fine, but it also does
	 * not fail loudly enough to notice a name that drifted out of the list above. The
	 * consequence is a table full of encrypted credentials left in the database of a site
	 * whose owner just deleted the plugin and reasonably believes it left nothing behind.
	 * At 0.10.1 the credential columns live on the accounts row, so there is no separate
	 * secrets table yet — the accounts table is the one that must be gone.
	 *
	 * @return void
	 */
	private function dropTables()
	{
		global $wpdb;

		$prefix = $wpdb->base_prefix;
		$incomplete = [];

		foreach($this->tables as $table)
		{
			$full = $prefix . $table;

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
			$wpdb->query('DROP TABLE IF EXISTS ' . $full);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($full))) === $full)
			{
				$incomplete[] = $table;
			}
		}

		if(!empty($incomplete))
		{
			// Report it rather than let "uninstalled" imply otherwise.
			update_option('wsklad_uninstall_incomplete', implode(', ', $incomplete) . ' could not be dropped', false);
		}
		else
		{
			delete_option('wsklad_uninstall_incomplete');
		}
	}

	/**
	 * Delete per-blog and network options.
	 *
	 * @return void
	 */
	private function deleteOptions()
	{
		foreach($this->options as $option)
		{
			delete_option($option);
		}

		$network =
		[
			'wsklad_version_database',
			'wsklad_schema_version',
			'wsklad_install_salt',
			'wsklad_key_id',
			'wsklad_migrations',
			'wsklad_migrations_failed',
		];

		if(is_multisite())
		{
			foreach($network as $option)
			{
				delete_site_option($option);
			}

			return;
		}

		foreach($network as $option)
		{
			delete_option($option);
		}
	}

	/**
	 * Remove the directories the plugin created.
	 *
	 * Only the plugin's own trees are touched, and only when they are empty of
	 * anything else — a directory that still holds unrelated files is left alone.
	 *
	 * @return void
	 */
	private function deleteFiles()
	{
		$paths = [];

		if(function_exists('wp_upload_dir'))
		{
			$uploads = wp_upload_dir();

			if(isset($uploads['basedir']))
			{
				$paths[] = $uploads['basedir'] . '/wsklad';
				$paths[] = dirname($uploads['basedir']) . '/wsklad';
			}
		}

		/**
		 * Let an extension add paths to clean up.
		 *
		 * @param array $paths
		 */
		$paths = apply_filters('wsklad_uninstall_paths', $paths);

		foreach($paths as $path)
		{
			$this->removeDirectory($path);
		}
	}

	/**
	 * Recursively delete a directory tree, but only inside a `wsklad` path.
	 *
	 * @param string $path
	 * @param int $depth
	 *
	 * @return void
	 */
	private function removeDirectory(string $path, int $depth = 0)
	{
		if($depth > 8 || '' === $path || false === strpos($path, 'wsklad'))
		{
			return;
		}

		if(!is_dir($path))
		{
			return;
		}

		// No `@`: an unreadable directory during an uninstall is a permission problem the
		// operator needs told about, not something to swallow while the "you are now
		// clean" notice is already on screen.
		$items = scandir($path);

		if(false === $items)
		{
			update_option('wsklad_uninstall_incomplete', 'could not read ' . $path, false);

			return;
		}

		foreach($items as $item)
		{
			if('.' === $item || '..' === $item)
			{
				continue;
			}

			$child = $path . '/' . $item;

			if(is_dir($child))
			{
				$this->removeDirectory($child, $depth + 1);

				continue;
			}

			// `wp_delete_file()` is the WordPress way and honours the same filters
			// `unlink()` would bypass. No `@`: an unremovable file means the directory
			// is still full of the operator's data, and that must not be silent.
			//
			// The `unlink()` branch is unreachable on any supported WordPress —
			// `wp_delete_file()` has existed since 4.9 — and exists so this class does
			// not fatal if it is ever run against something older.
			if(function_exists('wp_delete_file'))
			{
				wp_delete_file($child);
			}
			else
			{
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				unlink($child);
			}
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		rmdir($path);
	}
}
