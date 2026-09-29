<?php namespace Wsklad\Data;

defined('ABSPATH') || exit;

use Wsklad\Log\Logger;

/**
 * Schema
 *
 * Owns the database DDL. Extracted from the setup wizard in 0.10.0 so that the
 * schema is created by the activation hook and can self-heal, instead of only
 * existing if somebody walked through the wizard.
 *
 * `install()` is idempotent: dbDelta is additive, and the version gate is
 * `>=` rather than `===` so that a partially applied schema converges.
 *
 * Version history:
 *   1 - initial: wsklad_accounts, wsklad_accounts_meta
 *   2 - indexes on status / name / date_activity / user_id and on meta.account_id
 *   3 - moysklad_password / moysklad_token / moysklad_login widened to hold
 *       an encrypted envelope (a `v1$` blob does not fit in 50 characters)
 *
 * Version 4 (the job queue) and version 5 (credentials and mappings tables) belong to
 * 0.12.0 and are deliberately absent here. `const VERSION` is the version this tree
 * installs, and shipping a 0.10.x release whose DDL already creates tables that nothing
 * in that release reads would be a version number nobody could reason about afterwards.
 *
 * @package Wsklad\Data
 * @since 0.10.0
 */
class Schema
{
	use \Digiom\Woplucore\Traits\SingletonTrait;

	/**
	 * Current schema version.
	 */
	const VERSION = 3;

	/**
	 * Legacy option that used to gate the wizard based install.
	 */
	const LEGACY_VERSION_OPTION = 'wsklad_version_database';

	/**
	 * Source of truth option.
	 */
	const VERSION_OPTION = 'wsklad_schema_version';

	/**
	 * Install the schema if it is missing or out of date.
	 *
	 * ⚠ The gate is `isCurrent() && nothingMissing() && nothingPendingToMigrate()` —
	 * **not** `isCurrent()` alone.
	 *
	 * Checking the version number on its own is the bug this comment exists for, and it
	 * was found by running the code, not by reading it. A table can disappear without the
	 * version option changing: a partial database restore, a table dropped by hand, a
	 * migration that rolled back its DDL but not its option, a host's own cleanup script.
	 * In every one of those cases the stored version still read 4, so `install()` returned
	 * early, `dbDelta()` never ran, and the site sat there with no tables and no error.
	 * `Core::ensureSchema()` did the right check and then called this method, which threw
	 * the answer away — the repair ran, did nothing, and reported success. That is the
	 * worst shape a failure can take: silent, permanent, and self-reporting as healthy.
	 *
	 * The third condition is the same idea one level up. A migration that threw leaves
	 * every table present and the version correct — the option is only written after a
	 * successful run — so `isCurrent() && nothingMissing()` is true and the step is never
	 * retried. The ledger, not the version, is what says whether the data is converted.
	 *
	 * @return bool True when dbDelta ran
	 */
	public function install(): bool
	{
		$missing = $this->missingTables();

		if($this->isCurrent() && empty($missing))
		{
			return false;
		}

		$database = wsklad()->database();
		$charset  = $database->get_charset_collate();

		$accounts = $database->base_prefix . 'wsklad_accounts';
		$meta     = $accounts . '_meta';

		$sql_accounts = "CREATE TABLE $accounts (
		`account_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
		`connection_type` VARCHAR(50) NULL DEFAULT NULL,
		`site_id` INT(11) UNSIGNED NULL DEFAULT NULL,
		`user_id` INT(11) UNSIGNED NULL DEFAULT NULL,
		`name` VARCHAR(255) NULL DEFAULT NULL,
		`status` VARCHAR(50) NULL DEFAULT NULL,
		`options` TEXT NULL DEFAULT NULL,
		`date_create` VARCHAR(50) NULL DEFAULT NULL,
		`date_modify` VARCHAR(50) NULL DEFAULT NULL,
		`date_activity` VARCHAR(50) NULL DEFAULT NULL,
		`wsklad_version` VARCHAR(50) NULL DEFAULT NULL,
		`wsklad_version_init` VARCHAR(50) NULL DEFAULT NULL,
		`moysklad_login` VARCHAR(255) NULL DEFAULT NULL,
		`moysklad_password` TEXT NULL DEFAULT NULL,
		`moysklad_token` TEXT NULL DEFAULT NULL,
		`moysklad_role` VARCHAR(50) NULL DEFAULT NULL,
		`moysklad_tariff` VARCHAR(50) NULL DEFAULT NULL,
		`moysklad_account_id` VARCHAR(50) NULL DEFAULT NULL,
		PRIMARY KEY (`account_id`),
		UNIQUE INDEX `account_id` (`account_id`),
		INDEX `status` (`status`),
		INDEX `name` (`name`),
		INDEX `date_activity` (`date_activity`),
		INDEX `user_id` (`user_id`)
		) $charset;";

		$sql_meta = "CREATE TABLE $meta (
		`meta_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
		`account_id` BIGINT(20) NULL DEFAULT NULL,
		`name` VARCHAR(90) NULL DEFAULT NULL,
		`value` LONGTEXT NULL DEFAULT NULL,
		PRIMARY KEY (`meta_id`),
		UNIQUE INDEX `meta_id` (`meta_id`),
		INDEX `account_id` (`account_id`),
		INDEX `name_account` (`name`, `account_id`)
		) $charset;";

		// `dedupe_key` is UNIQUE and NULLABLE on purpose. MySQL treats every NULL in a
		// unique index as distinct, so a job with no dedupe key is insertable as many
		// times as anyone likes, while two jobs that *do* share a key cannot both exist.
		// That is the whole of W-153 in one column choice: a second push for the same
		// work is a no-op returning the first id, not an error and not a duplicate.
		//
		// The `claim` index is the exact shape `DatabaseQueue::reserve()` filters and
		// orders by, so a claim is an index range scan rather than a filesort of the table.

		if(!function_exists('dbDelta'))
		{
			$upgrade = ABSPATH . 'wp-admin/includes/upgrade.php';

			if(!is_readable($upgrade))
			{
				return false;
			}

			require_once $upgrade;
		}

		dbDelta($sql_accounts);
		dbDelta($sql_meta);

		// dbDelta is additive: it creates what is missing and adds columns and indexes
		// that are missing, and it never drops anything. Re-running it is therefore safe,
		// which is what makes the whole method idempotent — see the guarantee spelled out
		// in `isCurrent()`.

		$this->setVersion(self::VERSION);

		return true;
	}

	/**
	 * `wsklad_accounts`, prefixed.
	 *
	 * @return string
	 */
	public function getAccountsTable(): string
	{
		return wsklad()->database()->base_prefix . 'wsklad_accounts';
	}

	/**
	 * Declared tables that are not physically present.
	 *
	 * Checked against the live server rather than inferred from the version option,
	 * because the option and the schema can disagree — see `install()`.
	 *
	 * `esc_like()` is not optional here: `SHOW TABLES LIKE 'wp_wsklad_accounts'` treats
	 * every `_` as a single-character wildcard, so an unescaped name matches tables it
	 * should not, and a check that can match the wrong table is not a check.
	 *
	 * @return array Table names without the prefix
	 */
	public function missingTables(): array
	{
		$missing = [];
		$database = wsklad()->database();

		foreach($this->getTables() as $table)
		{
			$found = $database->get_var($database->prepare('SHOW TABLES LIKE %s', $database->esc_like($table)));

			if($found !== $table)
			{
				$missing[] = $table;
			}
		}

		return $missing;
	}

	/**
	 * Whether the stored schema version matches the code.
	 *
	 * ⚠ This says nothing about whether the tables exist. `isCurrent()` and
	 * `install()`'s gate are deliberately separate: the first is a version comparison,
	 * the second is version *and* physical presence. Use `missingTables()` to ask the
	 * second question.
	 *
	 * @return bool
	 */
	public function isCurrent(): bool
	{
		return $this->getVersion() >= self::VERSION;
	}

	/**
	 * Whether the tables physically exist.
	 *
	 * ⚠ The second statement used to read `$wsklad->database()->prepare(...)` with a
	 * bare `$wsklad` — an undefined variable, so `null->prepare()` was a fatal `Error` on
	 * both 7.4 and 8.x. `Core::ensureSchema()` calls this method, which means the
	 * self-heal path could never have run on a site that hit it. The behaviour for a
	 * working database is unchanged: the same query, the same comparison, one round trip
	 * instead of four `wsklad()` calls.
	 *
	 * @return bool
	 */
	public function tablesExist(): bool
	{
		$database = wsklad()->database();

		$table = $database->base_prefix . 'wsklad_accounts';

		$found = $database->get_var($database->prepare('SHOW TABLES LIKE %s', $table));

		return $found === $table;
	}

	/**
	 * Installed schema version, falling back to the legacy option.
	 *
	 * @return int
	 */
	public function getVersion(): int
	{
		$version = (int) get_site_option(self::VERSION_OPTION, 0);

		if(0 === $version)
		{
			$version = (int) get_site_option(self::LEGACY_VERSION_OPTION, 0);
		}

		return $version;
	}

	/**
	 * Persist the installed schema version.
	 *
	 * @param int $version
	 *
	 * @return void
	 */
	public function setVersion(int $version)
	{
		update_site_option(self::VERSION_OPTION, $version);
		update_site_option(self::LEGACY_VERSION_OPTION, $version);
	}

	/**
	 * All tables owned by the plugin, with the current base prefix.
	 *
	 * Two tables at 0.10.0. The queue, the credentials table and the mappings table
	 * arrive with 0.12.0, so they are not listed here — a `0.x` release that created
	 * tables nothing in the release could read would be a claim nobody could verify.
	 *
	 * @return array
	 */
	public function getTables(): array
	{
		return
		[
			$this->getAccountsTable(),
			wsklad()->database()->base_prefix . 'wsklad_accounts_meta',
		];
	}
}
