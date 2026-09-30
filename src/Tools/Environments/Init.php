<?php namespace Wsklad\Tools\Environments;

defined('ABSPATH') || exit;

use Wsklad\Data\Schema;
use Wsklad\Exceptions\Exception;
use Wsklad\Tools\Abstracts\ToolAbstract;
use Wsklad\Traits\UtilityTrait;

/**
 * Init
 *
 * @package Wsklad\Tools\Environments
 */
class Init extends ToolAbstract
{
	use UtilityTrait;

	/**
	 * @var array Wsklad data
	 */
	private $wsklad_data = [];

	/**
	 * @var array Server data
	 */
	private $server_data = [];

	/**
	 * @var array WordPress data
	 */
	private $wp_data = [];

	/**
	 * @var array WooCommerce data
	 */
	private $wc_data = [];

	/**
	 * Init constructor.
	 */
	public function __construct()
	{
		$this->init();
	}

	/**
	 * Initialize
	 */
	public function init()
	{
		$this->setId('environments');
		$this->setName(__('Environments', 'wsklad'));
		$this->setDescription(__('Everything WSKLAD can see about your setup.', 'wsklad'));

		if(!$this->utilityIsWskladAdminToolsRequest('environments'))
		{
			return;
		}

		add_action('wsklad_admin_tools_single_show', [$this, 'output']);

		/**
		 * Print
		 */
		add_filter('wsklad_admin_report_data_row_print', [$this, 'filter_data_row_print'], 10, 2);

		/**
		 * WSKLAD data output
		 */
		add_action('wsklad_admin_tools_single_show', [$this, 'wsklad_data_output'], 10);

		/**
		 * WC data output
		 */
		add_action('wsklad_admin_tools_single_show', [$this, 'wc_data_output'], 10);

		/**
		 * WP data output
		 */
		add_action('wsklad_admin_tools_single_show', [$this, 'wp_data_output'], 10);

		/**
		 * Server data output
		 */
		add_action('wsklad_admin_tools_single_show', [$this, 'server_data_output'], 10);
	}

	/**
	 * Show on page
	 */
	public function output()
	{
		//echo 'Example content';
	}

	/**
	 * Normalize data to print
	 *
	 * @param $data
	 *
	 * @return mixed
	 */
	public function filter_data_row_print($data)
	{
		/**
		 * Boolean
		 */
		if(is_bool($data))
		{
			if($data)
			{
				$data = __('yes', 'wsklad');
			}
			else
			{
				$data = __('not', 'wsklad');
			}
		}

		/**
		 * Array
		 */
		if(is_array($data))
		{
			$data = implode(', ', $data);
		}

		return $data;
	}

	/**
	 * WordPress data output
	 *
	 * @return void
	 */
	public function wp_data_output()
	{
		$wp_data = $this->load_wp_data();

		$args = ['title' => __('WordPress', 'wsklad'), 'data' => $wp_data];

		wsklad()->views()->getView('tools/environments/item.php', $args);
	}

	/**
	 * WSKLAD data output
	 *
	 * @return void
	 */
	public function wsklad_data_output()
	{
		$wsklad_data = $this->load_wsklad_data();

		$args = ['title' => __('WSKLAD', 'wsklad'), 'data' => $wsklad_data];

		wsklad()->views()->getView('tools/environments/item.php', $args);
	}

	/**
	 * WooCommerce data output
	 *
	 * @return void
	 */
	public function wc_data_output()
	{
		$wc_data = $this->load_wc_data();

		$args = ['title' => __('WooCommerce', 'wsklad'), 'data' => $wc_data];

		wsklad()->views()->getView('tools/environments/item.php', $args);
	}

	/**
	 * Server data output
	 *
	 * @return void
	 */
	public function server_data_output()
	{
		$server_data = $this->load_server_data();

		$args = ['title' => __('Server', 'wsklad'), 'data' => $server_data];

		wsklad()->views()->getView('tools/environments/item.php', $args);
	}

	/**
	 * WordPress data
	 *
	 * @return array
	 */
	public function load_wp_data()
	{
		/**
		 * Final
		 *
		 * title: show title, required
		 * description: optional
		 * data: raw data for entity
		 */
		$env_array = [];

		/**
		 * Home URL
		 */
		$env_array['wp_home_url'] = array
		(
			'title' => __('Home URL', 'wsklad'),
			'description' => '',
			'data' => get_option('home')
		);

		/**
		 * Site URL
		 */
		$env_array['wp_site_url'] = array
		(
			'title' => __('Site URL', 'wsklad'),
			'description' => '',
			'data' => get_option('siteurl')
		);

		/**
		 * Version
		 */
		$env_array['wp_version'] = array
		(
			'title' => __('WordPress version', 'wsklad'),
			'description' => '',
			'data' => get_bloginfo('version')
		);

		/**
		 * WordPress multisite
		 */
		$env_array['wp_multisite'] = array
		(
			'title' => __('WordPress multisite', 'wsklad'),
			'description' => '',
			'data' => is_multisite()
		);

		/**
		 * WordPress debug
		 */
		$env_array['wp_debug_mode'] = array
		(
			'title' => __('WordPress debug mode', 'wsklad'),
			'description' => '',
			'data' => (defined( 'WP_DEBUG' ) && WP_DEBUG)
		);

		/**
		 * WordPress debug
		 */
		$env_array['wp_cron'] = array
		(
			'title' => __('WordPress cron', 'wsklad'),
			'description' => '',
			'data' => !(defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON)
		);

		/**
		 * WordPress language
		 */
		$env_array['wp_language'] = array
		(
			'title' => __('WordPress language', 'wsklad'),
			'description' => '',
			'data' => get_locale()
		);

		/**
		 * WordPress memory limit
		 */
		$env_array['wp_memory_limit'] = array
		(
			'title' => __('WordPress memory limit', 'wsklad'),
			'description' => '',
			'data' => WP_MEMORY_LIMIT
		);

		/**
		 * Set wp data
		 */
		$this->set_wp_data($env_array);

		/**
		 * Return wp data
		 */
		return $this->get_wp_data();
	}

	/**
	 * Server data
	 */
	public function load_server_data(): array
    {
		/**
		 * Final
		 *
		 * title: show title, required
		 * description: optional
		 * data: raw data for entity
		 */
		$env_array = [];

		/**
		 * Server info
		 */
		$env_array['server_info'] = array
		(
			'title' => __('Server info', 'wsklad'),
			'description' => '',
			'data' => isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : '-'
		);

		/**
		 * PHP version
		 */
		$env_array['php_version'] = array
		(
			'title' => __('PHP version', 'wsklad'),
			'description' => '',
			'data' => PHP_VERSION
		);

		/**
		 * Database version
		 */
		$env_array['db_version'] = array
		(
			'title' => __('Database version', 'wsklad'),
			'description' => '',
			'data' => (!empty(wsklad()->database()->is_mysql) ? wsklad()->database()->db_version() : '-')
		);

		/**
		 * Suhosin
		 */
		$env_array['suhosin_installed'] = array
		(
			'title' => __('Suhosin', 'wsklad'),
			'description' => '',
			'data' => extension_loaded('suhosin')
		);

		/**
		 * Fsockopen or curl enabled
		 */
		$env_array['fsockopen_or_curl'] = array
		(
			'title' => __('FSockopen or cURL available', 'wsklad'),
			'description' => '',
			'data' => (function_exists('fsockopen') || function_exists('curl_init'))
		);

		/**
		 * CURL
		 */
		if(function_exists('curl_version'))
		{
			$curl_version = curl_version();

			$env_array['curl_version'] = array
			(
				'title' => __('CURL info', 'wsklad'),
				'description' => '',
				'data' => $curl_version['version'] . ', ' . $curl_version['ssl_version']
			);
		}

		/**
		 * Default timezone
		 */
		$env_array['default_timezone'] = array
		(
			'title' => __('Default timezone', 'wsklad'),
			'description' => '',
			'data' => date_default_timezone_get()
		);

		/**
		 * PHP post max size
		 */
		$env_array['php_post_max_size'] = array
		(
			'title' => __('PHP post max size', 'wsklad'),
			'description' => '',
			'data' => ini_get('post_max_size')
		);

		/**
		 * PHP max upload size
		 */
		$env_array['php_max_upload_size'] = array
		(
			'title' => __('PHP max upload size', 'wsklad'),
			'description' => '',
			'data' => (wp_max_upload_size() / 1024 / 1024) . 'M'
		);

		/**
		 * PHP max execution time
		 */
		$env_array['php_max_execution_time'] = array
		(
			'title' => __('PHP max execution time', 'wsklad'),
			'description' => '',
			'data' => ini_get('max_execution_time')
		);

		/**
		 * PHP max input vars
		 */
		$env_array['php_max_input_vars'] = array
		(
			'title' => __('PHP max input vars', 'wsklad'),
			'description' => '',
			'data' => ini_get('max_input_vars')
		);

		/**
		 * PHP soapclient enabled
		 */
		$env_array['php_soapclient_enabled'] = array
		(
			'title' => __('PHP SoapClient available', 'wsklad'),
			'description' => '',
			'data' => class_exists('SoapClient')
		);

		/**
		 * PHP domdocument enabled
		 */
		$env_array['php_domdocument_enabled'] = array
		(
			'title' => __('PHP DOMDocument available', 'wsklad'),
			'description' => '',
			'data' => class_exists('DOMDocument')
		);

		/**
		 * PHP gzip enabled
		 */
		$env_array['php_gzip_enabled'] = array
		(
			'title' => __('PHP gzip available', 'wsklad'),
			'description' => '',
			'data' => is_callable('gzopen')
		);

		/**
		 * PHP mbstring enabled
		 */
		$env_array['php_mbstring_enabled'] = array
		(
			'title' => __('PHP mbstring available', 'wsklad'),
			'description' => '',
			'data' => extension_loaded('mbstring')
		);

		/**
		 * Set server data
		 */
		$this->set_server_data($env_array);

		/**
		 * Return final server data
		 */
		return $this->get_server_data();
	}

	/**
	 * WSKLAD data
	 */
	public function load_wsklad_data()
	{
		/**
		 * Container
		 */
		$env_array = [];

		/**
		 * WSKLAD version
		 */
		$env_array['wsklad_version'] = array
		(
			'title' => __('WSKLAD version', 'wsklad'),
			'description' => '',
			'data' => wsklad()->environment()->get('wsklad_version', '')
		);

		/**
		 * WSKLAD upload directory
		 */
		$env_array['wsklad_upload_directory'] = array
		(
			'title' => __('Upload directory', 'wsklad'),
			'description' => '',
			'data' => wsklad()->environment()->get('wsklad_upload_directory')
		);

		/**
		 * Extensions count
		 */
		try
		{
			$extensions = wsklad()->extensions()->get();
			$env_array['wsklad_extensions_count'] = array
			(
				'title' => __('Count extensions', 'wsklad'),
				'description' => '',
				'data' => count($extensions)
			);
		}
		catch(Exception $e){}

		/**
		 * Tools count
		 */
		try
		{
			$tools = wsklad()->tools()->get();
			$env_array['wsklad_tools_count'] = array
			(
				'title' => __('Count tools', 'wsklad'),
				'description' => '',
				'data' => count($tools)
			);
		}
		catch(Exception $e)
		{}

		/**
		 * Schema integrity
		 */
		$env_array = array_merge($env_array, $this->schema_data());

		$this->set_wsklad_data($env_array);

		return $this->get_wsklad_data();
	}

	/**
	 * Whether the tables on this site are the tables the release expects.
	 *
	 * `Schema::isCurrent()` compares the *number* in an option against the number in
	 * the code, and `tablesExist()` asks whether two table names answer. Between them
	 * they cannot see a table that is present and wrong: a column someone dropped by
	 * hand, a `varchar` that is too short for the values already in it, an index that
	 * a migration never created, or a metadata row whose account is gone. The version
	 * option still says 3, both tables still answer, and the failure arrives later as
	 * a MySQL error in whatever the owner happened to be doing at the time — which is
	 * how this report came to be needed at all.
	 *
	 * Read only. It reports, it never repairs: a tool that quietly rewrites a table
	 * behind an owner's back is a much worse thing to hand someone than a report.
	 *
	 * @return array
	 */
	public function schema_data(): array
	{
		$report = wsklad()->schema()->inspect();

		$env_array = [];

		$env_array['wsklad_schema_version'] = array
		(
			'title' => __('Schema version', 'wsklad'),
			'description' => __('The version the code declares, against the one recorded on this site. A number alone proves nothing — that is the gap the rows below close.', 'wsklad'),
			'data' => sprintf('%s / %s', Schema::VERSION, (string) get_option(Schema::VERSION_OPTION, '0'))
		);

		$env_array['wsklad_schema_integrity'] = array
		(
			'title' => __('Database structure', 'wsklad'),
			'description' => __('Whether every table, column, column type and index the release expects is really there. Check this first when an account screen reports a database error.', 'wsklad'),
			'data' => $report['ok'] && 0 === $report['orphans']
				? __('matches what the release expects', 'wsklad')
				: __('differs from what the release expects — see the rows below', 'wsklad')
		);

		$env_array['wsklad_schema_tables_missing'] = array
		(
			'title' => __('Missing tables', 'wsklad'),
			'description' => '',
			'data' => $this->schema_detail($report['tables_missing'])
		);

		$env_array['wsklad_schema_columns_missing'] = array
		(
			'title' => __('Missing columns', 'wsklad'),
			'description' => '',
			'data' => $this->schema_detail($report['columns_missing'])
		);

		$env_array['wsklad_schema_columns_mistyped'] = array
		(
			'title' => __('Columns of the wrong type', 'wsklad'),
			'description' => __('A `varchar` shorter than the one the release creates will refuse values it used to accept, and the row it refuses is invisible until someone tries to save it.', 'wsklad'),
			'data' => $this->schema_detail($report['columns_mistyped'])
		);

		$env_array['wsklad_schema_indexes_missing'] = array
		(
			'title' => __('Missing indexes', 'wsklad'),
			'description' => '',
			'data' => $this->schema_detail($report['indexes_missing'])
		);

		$env_array['wsklad_schema_orphans'] = array
		(
			'title' => __('Metadata rows with no account', 'wsklad'),
			'description' => __('Rows in the metadata table pointing at an account that does not exist. They are left behind by any process that removes an account without cleaning up after it, and they are not visible from the account screen.', 'wsklad'),
			'data' => (string) $report['orphans']
		);

		return $env_array;
	}

	/**
	 * Flatten one part of the report into something printable.
	 *
	 * The report is keyed by table name, because two tables can each be missing a
	 * different column, and a table name on its own is not an answer to "what is
	 * wrong". Where every table is fine, the empty list prints as a dash rather than
	 * as an empty cell, which is otherwise indistinguishable from a rendering fault.
	 *
	 * @param array $part
	 *
	 * @return string
	 */
	private function schema_detail(array $part): string
	{
		if(empty($part))
		{
			return '-';
		}

		$lines = [];

		foreach($part as $table => $entries)
		{
			if(is_string($entries))
			{
				$lines[] = $entries;
				continue;
			}

			foreach((array) $entries as $key => $value)
			{
				$lines[] = is_int($key) ? $table . ': ' . $value : $table . ': ' . $key . ' ' . $value;
			}
		}

		return implode(', ', $lines);
	}

	/**
	 * The WooCommerce order storage mode.
	 *
	 * Answers the one question an integrator cannot get from the admin otherwise:
	 * **are orders in the posts tables, or in the dedicated `wc_orders` tables?**
	 * It is the first thing anyone comparing a raw SQL log against this plugin has
	 * to know, and until now the screen said nothing about it.
	 *
	 * Four states, not two, and the extra two are the point:
	 *
	 * | state | table | when |
	 * |---|---|---|
	 * | `HPOS` | `{$prefix}wc_orders` | `OrderUtil::custom_orders_table_usage_is_enabled()` is true |
	 * | `post tables` | `{$prefix}posts` | the same call is available and false |
	 * | `unknown (WooCommerce not active)` | — | WooCommerce is not installed |
	 * | `unknown (too old to ask)` | — | WooCommerce predates 8.2 and the option is absent |
	 *
	 * A binary answer would have to answer one of the last two with a guess, and a
	 * wrong "post tables" on a site with no WooCommerce at all is worse than no
	 * answer: it is a confident statement about a fact nobody can check.
	 *
	 * `OrderUtil` is autoloaded only from WooCommerce 8.2, which is why it is
	 * probed with `class_exists` and `method_exists` and never called
	 * unconditionally — calling it on 7.x is a fatal, and a diagnostics screen is
	 * the last place that should be able to take a site down.
	 *
	 * @return array{state: string, label: string, table: string, source: string, hpos: bool}
	 */
	public function order_storage(): array
	{
		$result =
		[
			'state'  => 'unknown',
			'label'  => __('unknown (WooCommerce is not active)', 'wsklad'),
			'table'  => '',
			'source' => '',
			'hpos'   => false,
		];

		if(!$this->is_woocommerce_active())
		{
			return $result;
		}

		$enabled = $this->order_storage_enabled();

		if(is_null($enabled))
		{
			/**
			 * WooCommerce is here but will not answer. Old versions have no
			 * `OrderUtil`; the pre-8.2 option is the documented fallback, and if it
			 * is missing too the honest answer is "unknown", never "post tables".
			 */
			$result['state']  = 'unknown';
			$result['label']  = __('unknown (this version of WooCommerce cannot report where orders are stored)', 'wsklad');
			$result['source'] = __('neither OrderUtil nor the woocommerce_custom_orders_table_enabled option', 'wsklad');

			return $result;
		}

		$prefix = $this->table_prefix();

		if($enabled)
		{
			$result['state']  = 'hpos';
			$result['label']  = __('HPOS (High-Performance Order Storage)', 'wsklad');
			$result['table']  = $prefix . 'wc_orders';
			$result['source'] = 'OrderUtil::custom_orders_table_usage_is_enabled()';
			$result['hpos']   = true;

			return $result;
		}

		$result['state']  = 'posts';
		$result['label']  = __('post tables', 'wsklad');
		$result['table']  = $prefix . 'posts';
		$result['source'] = 'OrderUtil::custom_orders_table_usage_is_enabled()';
		$result['hpos']   = false;

		return $result;
	}

	/**
	 * The order storage mode as screen rows.
	 *
	 * Separate from `order_storage()` so the detection is usable on its own — the
	 * answer is needed by more than this one table, and a detection method that only
	 * exists to be printed cannot be printed by a second screen without copying it.
	 *
	 * @return array
	 */
	public function order_storage_data(): array
	{
		$storage = $this->order_storage();

		$env_array = [];

		$env_array['wc_order_storage'] = array
		(
			'title' => __('Where orders are stored', 'wsklad'),
			'description' => __('Whether WooCommerce keeps orders in the posts tables or in HPOS — High-Performance Order Storage, the name WooCommerce 8.2+ uses for its own order tables.', 'wsklad'),
			'data' => $storage['label']
		);

		$env_array['wc_order_storage_table'] = array
		(
			'title' => __('Orders table', 'wsklad'),
			'description' => __('The table an order is really a row in. Compare this against a SQL log before drawing a conclusion.', 'wsklad'),
			'data' => '' !== $storage['table'] ? $storage['table'] : '-'
		);

		$env_array['wc_order_storage_source'] = array
		(
			'title' => __('Detected from', 'wsklad'),
			'description' => __('The signal that answered. An empty value means nothing could.', 'wsklad'),
			'data' => '' !== $storage['source'] ? $storage['source'] : '-'
		);

		return $env_array;
	}

	/**
	 * Is WooCommerce loaded at all?
	 *
	 * `WC_VERSION` is defined by woocommerce.php and is the reliable signal;
	 * `function_exists('WC')` alone is not, because several other plugins define it.
	 *
	 * @return bool
	 */
	private function is_woocommerce_active(): bool
	{
		return defined('WC_VERSION') || class_exists('WooCommerce');
	}

	/**
	 * Ask WooCommerce where orders are, or null when it will not say.
	 *
	 * @return bool|null
	 */
	private function order_storage_enabled()
	{
		if
		(
			class_exists('Automattic\WooCommerce\Utilities\OrderUtil')
			&& method_exists('Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled')
		)
		{
			try
			{
				return (bool) \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
			}
			catch(\Throwable $e)
			{
				return null;
			}
		}

		/**
		 * WooCommerce 7.x: the mode is an option. Only a real, non-empty value is
		 * trusted — an option that is not there means this build has neither signal,
		 * and guessing "post tables" there is exactly the wrong answer to give.
		 */
		if(function_exists('get_option'))
		{
			$legacy = get_option('woocommerce_custom_orders_table_enabled', '');

			if('' !== $legacy && !is_null($legacy))
			{
				return 'yes' === $legacy;
			}
		}

		return null;
	}

	/**
	 * The table prefix, with a sane fallback when there is no database yet.
	 *
	 * @return string
	 */
	private function table_prefix(): string
	{
		if(isset($GLOBALS['wpdb']) && is_object($GLOBALS['wpdb']) && isset($GLOBALS['wpdb']->prefix))
		{
			return (string) $GLOBALS['wpdb']->prefix;
		}

		return 'wp_';
	}

	/**
	 * WooCommerce data
	 */
	private function load_wc_data()
	{
		/**
		 * Container
		 */
		$env_array = [];

		/**
		 * Order storage goes in first, and is present in every state.
		 *
		 * It is the only row in this table that is worth having on a site without
		 * WooCommerce, so the table is no longer skipped when WooCommerce is absent —
		 * it says so instead.
		 */
		$env_array = array_merge($env_array, $this->order_storage_data());

		if(!$this->is_woocommerce_active())
		{
			$env_array['wc_version'] = array
			(
				'title' => __('WooCommerce version', 'wsklad'),
				'description' => '',
				'data' => __('not active', 'wsklad')
			);

			/**
			 * Final set
			 */
			$this->set_wc_data($env_array);

			return $this->get_wc_data();
		}

		/**
		 * WooCommerce version
		 */
		$env_array['wc_version'] = array
		(
			'title' => __('WooCommerce version', 'wsklad'),
			'description' => '',
			'data' => function_exists('WC') && is_object(WC()) ? WC()->version : WC_VERSION
		);

		$term_response = [];

		/**
		 * Everything below is WooCommerce's own API. `is_woocommerce_active()` proves
		 * WooCommerce exists, not that its function API has been declared yet — and
		 * this screen is, of all places, allowed to say "not available" instead of
		 * producing a fatal.
		 */
		if(function_exists('get_woocommerce_currency'))
		{
			$terms = get_terms('product_type');

			if(is_array($terms))
			{
				foreach($terms as $term)
				{
					$term_response[$term->slug] = strtolower($term->name);
				}
			}

			/**
			 * Product types
			 */
			$env_array['wc_product_types'] = array
			(
				'title' => __('WooCommerce product types', 'wsklad'),
				'description' => '',
				'data' => $term_response
			);

			/**
			 * WooCommerce currency
			 */
			$env_array['wc_currency'] = array
			(
				'title' => __('WooCommerce currency', 'wsklad'),
				'description' => '',
				'data' => get_woocommerce_currency()
			);

			/**
			 * WooCommerce currency symbol
			 */
			$env_array['wc_currency_symbol'] = array
			(
				'title' => __('WooCommerce currency symbol', 'wsklad'),
				'description' => '',
				'data' => get_woocommerce_currency_symbol()
			);
		}

		/**
		 * Final set
		 */
		$this->set_wc_data($env_array);

		/**
		 * Return all data
		 */
		return $this->get_wc_data();
	}

	/**
	 * Get WooCommerce data
	 *
	 * @return array
	 */
	public function get_wc_data(): array
    {
		return $this->wc_data;
	}

	/**
	 * Set WooCommerce data
	 *
	 * @param array $wc_data
	 */
	public function set_wc_data(array $wc_data)
	{
		$this->wc_data = $wc_data;
	}

	/**
	 * @return array
	 */
	public function get_wsklad_data(): array
    {
		return $this->wsklad_data;
	}

	/**
	 * @param array $wsklad_data
	 */
	public function set_wsklad_data(array $wsklad_data)
	{
		$this->wsklad_data = $wsklad_data;
	}

	/**
	 * @return array
	 */
	public function get_server_data(): array
    {
		return $this->server_data;
	}

	/**
	 * @param array $server_data
	 */
	public function set_server_data(array $server_data)
	{
		$this->server_data = $server_data;
	}

	/**
	 * @return array
	 */
	public function get_wp_data(): array
    {
		return $this->wp_data;
	}

	/**
	 * @param array $wp_data
	 */
	public function set_wp_data(array $wp_data)
	{
		$this->wp_data = $wp_data;
	}
}