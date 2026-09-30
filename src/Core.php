<?php namespace Wsklad;

defined('ABSPATH') || exit;

use wpdb;
use Digiom\Woplucore\Interfaces\SettingsInterface;
use Digiom\Woplucore\Abstracts\CoreAbstract;
use Digiom\Woplucore\Traits\SingletonTrait;
use Psr\Log\LoggerInterface;
use Wsklad\Data\Schema;
use Wsklad\Log\Formatter;
use Wsklad\Log\Handler;
use Wsklad\Log\Logger;
use Wsklad\Log\Processor;
use Wsklad\Security\Cryptography;
use Wsklad\Security\KeyProvider;
use Wsklad\Settings\InterfaceSettings;
use Wsklad\Settings\LogsSettings;
use Wsklad\Settings\MainSettings;

/**
 * Core
 *
 * @package Wsklad
 */
final class Core extends CoreAbstract
{
	use SingletonTrait;

	/**
	 * @var array
	 */
	private $log = [];

	/**
	 * @var Timer
	 */
	private $timer;

	/**
	 * @var SettingsInterface
	 */
	private $settings = [];

	/**
	 * @var Cryptography|null
	 */
	private $cryptography;

	/**
	 * @var KeyProvider|null
	 */
	private $key_provider;

	/**
	 * Core constructor.
	 *
	 * @return void
	 */
	public function __construct()
	{
		do_action('wsklad_core_loaded');
	}

	/**
	 * Initialization
	 */
	public function init()
	{
		// hook
		do_action('wsklad_before_init');

		$this->localization();

		$this->ensureDirectories();
		$this->ensureSchema();

		try
		{
			$this->timer();
		}
		catch(\Throwable $e)
		{
			wsklad()->log()->alert(__('The timer did not load.', 'wsklad'), ['exception' => $e]);
			return;
		}

		try
		{
			$this->extensions()->load();
		}
		catch(\Throwable $e)
		{
			wsklad()->log()->alert(__('The extensions did not load.', 'wsklad'), ['exception' => $e]);
		}

		try
		{
			$this->extensions()->init();
		}
		catch(\Throwable $e)
		{
			wsklad()->log()->alert(__('The extensions were not initialised.', 'wsklad'), ['exception' => $e]);
		}

		try
		{
			$this->tools()->load();
		}
		catch(\Throwable $e)
		{
			wsklad()->log()->alert(__('The tools did not load.', 'wsklad'), ['exception' => $e]);
		}

		if(false !== wsklad()->context()->isAdmin())
		{
			try
			{
				$this->tools()->init();
			}
			catch(\Throwable $e)
			{
				wsklad()->log()->alert(__('The tools were not initialised.', 'wsklad'), ['exception' => $e]);
			}
		}

		try
		{
			\Wsklad\Privacy\Privacy::register();
		}
		catch(\Throwable $e)
		{
			$this->log()->error($e->getMessage(), ['exception' => $e]);
		}

		// hook
		do_action('wsklad_after_init');
	}

	/**
	 * Create the directories the plugin writes into and drop protective files in them.
	 *
	 * Runs on `admin_init` only, and at most once a day, so that a front-end request
	 * never pays for a filesystem walk.
	 *
	 * @return void
	 */
	public function ensureDirectories()
	{
		if(!is_admin() || wp_doing_ajax())
		{
			return;
		}

		$stamp = get_option('wsklad_directories_checked', 0);

		if((int) $stamp === (int) time())
		{
			return;
		}

		try
		{
			$this->environment()->protectDirectories();
		}
		catch(\Throwable $e)
		{
			// A read-only or missing parent must never be fatal — W-623 wants a notice, not a crash.
			update_option('wsklad_directories_error', $e->getMessage(), false);
		}

		update_option('wsklad_directories_checked', time(), false);
	}

	/**
	 * Self-heal the schema.
	 *
	 * Before 0.10.0 the tables were only created by the setup wizard, so any path that
	 * skipped the wizard left the site without a database. This re-creates them, throttled
	 * to once a day, and leaves a notice so the condition is visible rather than silent.
	 *
	 * @return bool
	 */
	public function ensureSchema(): bool
	{
		if(!is_admin() || wp_doing_ajax())
		{
			return false;
		}

		$this->ensureVersionMigration();

		$schema = $this->schema();

		$missing = $schema->missingTables();

		if($schema->isCurrent() && empty($missing))
		{
			return true;
		}

		$stamp = get_option('wsklad_schema_checked', 0);

		if((int) $stamp === (int) time())
		{
			return false;
		}

		update_option('wsklad_schema_checked', time(), false);

		try
		{
			$installed = $schema->install();
		}
		catch(\Throwable $e)
		{
			$this->log()->error($e->getMessage(), ['exception' => $e]);

			return false;
		}

		if(!$installed)
		{
			// install() declined to run, yet the tables are still not all there. Saying
			// nothing here is how this condition stayed invisible in the first place.
			$this->log()->error
			(
				'Schema install declined but tables are still missing.',
				['missing' => $missing, 'version' => $schema->getVersion()]
			);

			$this->schemaNotice(false);

			return false;
		}

		$this->schemaNotice(true, $missing);

		return true;
	}

	/**
	 * Run the update wizard once after the plugin version changes.
	 *
	 * `UpdateWizard` used to be an empty `// TODO`, so a DDL change shipped in a plugin
	 * update was never applied. It now runs from here, keyed on the stored version, so
	 * the migration happens on the first admin request after an update.
	 *
	 * @return bool
	 */
	public function ensureVersionMigration(): bool
	{
		$version = (string) $this->environment()->get('wsklad_version');
		$stored  = (string) get_option('wsklad_version_active', '');

		if($version === $stored)
		{
			return false;
		}

		$applied = false;

		try
		{
			$applied = Admin\Wizards\UpdateWizard::instance()->init();
		}
		catch(\Throwable $e)
		{
			$this->log()->error($e->getMessage(), ['exception' => $e]);
		}

		update_option('wsklad_version_active', $version, true);

		return $applied;
	}

	/**
	 * Tell the administrator that the schema was missing and has just been created.
	 *
	 * The repaired message names the tables that were actually missing. "Your database
	 * is broken" and "these three tables were gone" are different conversations, and
	 * only the second one tells anyone where to start.
	 *
	 * @param bool $repaired
	 * @param array $missing
	 *
	 * @return void
	 */
	public function schemaNotice(bool $repaired = false, array $missing = [])
	{
		if($repaired && !empty($missing))
		{
			$data = sprintf
			(
				/* translators: %s: comma separated table names */
				__('WSKLAD recreated the missing database tables: %s. If you removed them yourself, you can ignore this notice.', 'wsklad'),
				implode(', ', $missing)
			);
		}
		elseif($repaired)
		{
			$data = __('The WSKLAD database tables were missing and have just been recreated. If you deleted the plugin options yourself, you can ignore this notice.', 'wsklad');
		}
		else
		{
			$data = __('The WSKLAD database tables are missing and could not be recreated. Deactivate and reactivate the plugin; if that does not help, check the file permissions in wp-content.', 'wsklad');
		}

		$this->admin()->notices()->create
		(
			[
				'id' => 'wsklad_schema_missing',
				'dismissible' => false,
				'type' => $repaired ? 'success' : 'error',
				'data' => $data,
			]
		);
	}

	/**
	 * Extensions
	 *
	 * @return Extensions\Core
	 */
	public function extensions(): Extensions\Core
	{
		return Extensions\Core::instance();
	}

	/**
	 * Filesystem
	 *
	 * @return Filesystem
	 */
	public function filesystem(): Filesystem
	{
		return Filesystem::instance();
	}

	/**
	 * Environment
	 *
	 * @return Environment
	 */
	public function environment(): Environment
	{
		return Environment::instance();
	}

	/**
	 * Schema
	 *
	 * @return Schema
	 */
	public function schema(): Schema
	{
		return Schema::instance();
	}

	/**
	 * Cryptography used for Moy Sklad credentials.
	 *
	 * Returns a real implementation when libsodium is present, and a no-op that
	 * reports itself unavailable otherwise. Callers must check `isAvailable()`
	 * before assuming a write was encrypted.
	 *
	 * ⚠ This goes through `keyProvider()` rather than building its own. It used to
	 * `new KeyProvider()` here, which gave the core two independent providers: one for
	 * the key, one for the cipher. Rotating the key id then reset the cipher cache on
	 * the *other* object, the memoised instance kept writing `v1$k1$` after a rotation,
	 * and rotation appeared to do nothing until the next request. Found by running the
	 * integration suite, not by reading this.
	 *
	 * @return Cryptography
	 */
	public function cryptography(): Cryptography
	{
		if(is_null($this->cryptography))
		{
			$this->cryptography = $this->keyProvider()->get();
		}

		return $this->cryptography;
	}

	/**
	 * Key provider, exposed for rotation and diagnostics.
	 *
	 * @return KeyProvider
	 */
	public function keyProvider(): KeyProvider
	{
		if(is_null($this->key_provider))
		{
			$this->key_provider = new KeyProvider();
		}

		return $this->key_provider;
	}

	/**
	 * Bump the encryption key id and drop every cached key material.
	 *
	 * The single entry point for rotation. Calling `KeyProvider::rotateKeyId()` directly
	 * would leave `Core::$cryptography` holding the old envelope version, which is
	 * exactly the bug this method exists to make impossible.
	 *
	 * ⚠ Rotating the key id does **not** change the key material — it marks the point
	 * from which new writes are labelled. Existing rows keep their old id and stay
	 * readable. Changing the actual key requires re-entering every credential, which is
	 * documented in UPGRADE.md.
	 *
	 * @return string The new key id
	 */
	public function rotateKeyId(): string
	{
		$next = $this->keyProvider()->rotateKeyId();

		$this->cryptography = null;

		return $next;
	}

	/**
	 * Views
	 *
	 * @return Views
	 */
	public function views(): Views
	{
		return Views::instance()->setSlug('wsklad')->setPluginDir($this->environment()->get('plugin_directory_path'));
	}

	/**
	 * Tools
	 *
	 * @return Tools\Core
	 */
	public function tools(): Tools\Core
	{
		return Tools\Core::instance();
	}

	/**
	 * Logger
	 *
	 * @param string $channel
	 * @param string $name
	 * @param mixed $hard_level
	 *
	 * @return LoggerInterface
	 */
	public function log(string $channel = 'main', string $name = '', $hard_level = null)
	{
		$channel = strtolower($channel);

		if(!isset($this->log[$channel]))
		{
			if('' === $name)
			{
				$name = $channel;
			}

			$path = '';
			$max_files = $this->settings('logs')->get('logger_files_max', 30);

			$logger = new Logger($channel);

			switch($channel)
			{
				case 'tools':
					$path = $this->environment()->get('wsklad_tools_logs_directory') . '/' . $name . '.log';
					$level = $this->settings('logs')->get('logger_tools_level', 'logger_level');
					break;
				case 'accounts':
					$path = $name . '.log';
					$level = $this->settings('logs')->get('logger_accounts_level', 'logger_level');
					break;
				default:
					$level = $this->settings('logs')->get('logger_level', 300);
			}

			if('logger_level' === $level)
			{
				$level = $this->settings('logs')->get('logger_level', 300);
			}

			if(!is_null($hard_level))
			{
				$level = $hard_level;
			}

			if('' === $path)
			{
				$path = $this->environment()->get('wsklad_logs_directory') . '/main.log';
			}

			try
			{
				$uid_processor = new Processor();
				$formatter = new Formatter();
				$handler = new Handler($path, $max_files, $level);

				$handler->setFormatter($formatter);

				$logger->pushProcessor($uid_processor);
				$logger->pushHandler($handler);
			}
			catch(\Throwable $e){}

			/**
			 * Extension point for replacing the logger.
			 *
			 * @param LoggerInterface $logger The logger built so far
			 *
			 * @return LoggerInterface
			 */
			if(has_filter('wsklad_log_load_before'))
			{
				$logger = apply_filters('wsklad_log_load_before', $logger);
			}

			$this->log[$channel] = $logger;
		}

		return $this->log[$channel];
	}

	/**
	 * Settings
	 *
	 * @param string $context
	 *
	 * @return SettingsInterface
	 */
	public function settings(string $context = 'main')
	{
		if(!isset($this->settings[$context]))
		{
			switch($context)
			{
				case 'logs':
					$class = LogsSettings::class;
					break;
				case 'interface':
					$class = InterfaceSettings::class;
					break;
				default:
					$class = MainSettings::class;
			}

			$settings = new $class();

			try
			{
				$settings->init();
			}
			catch(\Throwable $e)
			{
				wsklad()->log()->error($e->getMessage(), ['exception' => $e]);
			}

			$this->settings[$context] = $settings;
		}

		return $this->settings[$context];
	}

	/**
	 * Timer
	 *
	 * @return Timer
	 */
	public function timer(): Timer
    {
		if(is_null($this->timer))
		{
			$timer = new Timer();

			$php_max_execution = $this->environment()->get('php_max_execution_time', 20);

			if($php_max_execution !== $this->settings()->get('php_max_execution_time', $php_max_execution))
			{
				$php_max_execution = $this->settings()->get('php_max_execution_time', $php_max_execution);
			}

			$timer->setMaximum($php_max_execution);

			$this->timer = $timer;
		}

		return $this->timer;
	}

	/**
	 * Load localisation
	 */
	public function localization()
	{
		$locale = determine_locale();

		if(has_filter('plugin_locale'))
		{
			$locale = apply_filters('plugin_locale', $locale, 'wsklad');
		}

		load_textdomain('wsklad', WP_LANG_DIR . '/plugins/wsklad-' . $locale . '.mo');
		load_textdomain('wsklad', wsklad()->environment()->get('plugin_directory_path') . 'assets/languages/wsklad-' . $locale . '.mo');

		wsklad()->log()->debug(__('The translation was loaded.', 'wsklad'), ['locale' => $locale]);
	}

	/**
	 * Use in plugin for DB queries
	 *
	 * @return wpdb
	 */
	public function database(): wpdb
	{
		global $wpdb;
		return $wpdb;
	}

	/**
	 * Main instance of Admin
	 *
	 * @return Admin
	 */
	public function admin(): Admin
	{
		// A buffer opened here and never closed leaks on every call. Only open the
		// buffer once, and close it with the response.
		if(!self::$admin_buffer_opened)
		{
			ob_start();

			self::$admin_buffer_opened = true;

			add_action('shutdown', [__CLASS__, 'flushAdminBuffer'], 1);
		}

		return Admin::instance();
	}

	/**
	 * @var bool
	 */
	private static $admin_buffer_opened = false;

	/**
	 * Close the output buffer opened by admin(), if one is still open.
	 *
	 * @return void
	 */
	public static function flushAdminBuffer()
	{
		if(self::$admin_buffer_opened)
		{
			self::$admin_buffer_opened = false;

			if(ob_get_level() > 0)
			{
				// No `@`. A failure here means a buffer we opened cannot be closed, which
				// is worth seeing; swallowing it is how a response ends up missing its
				// first kilobyte with nothing in the log.
				ob_end_flush();
			}
		}
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
	public function getVar(&$var, $default = null)
	{
		return $var ?? $default;
	}


	/**
	 * Define constant if not already set
	 *
	 * @param string $name constant name
	 * @param string|bool $value constant value
	 */
	public function define(string $name, $value)
	{
		if(!defined($name))
		{
			define($name, $value);
		}
	}
}