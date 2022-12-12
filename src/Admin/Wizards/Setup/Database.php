<?php namespace Wsklad\Admin\Wizards\Setup;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Wsklad\Admin\Wizards\StepAbstract;

/**
 * Database
 *
 * @package Wsklad\Admin\Wizards
 */
class Database extends StepAbstract
{
	use SingletonTrait;

	/**
	 * Database constructor.
	 */
	public function __construct()
	{
		$this->setId('database');
	}

	/**
	 * Precessing step
	 */
	public function process()
	{
		if(isset($_POST['_wsklad-admin-nonce']))
		{
			if(wp_verify_nonce($_POST['_wsklad-admin-nonce'], 'wsklad-admin-wizard-database'))
			{
				$this->tablesInstall();
				wp_safe_redirect($this->wizard()->getNextStepLink());
				die;
			}

			wsklad()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => __('Create tables error. Please retry.', 'wsklad')
				]
			);
		}

		add_action('wsklad_wizard_content_output', [$this, 'output'], 10);
	}

	/**
	 * Output wizard content
	 *
	 * @return void
	 */
	public function output()
	{
		$args =
		[
			'step' => $this
		];

		wsklad()->views()->getView('wizards/steps/database.php', $args);
	}

	/**
	 * Install db tables
	 *
	 * @return bool
	 */
	public function tablesInstall()
	{
		$wsklad_version_database = 1;

		$current_db = get_site_option('wsklad_version_database', 0);

		if($current_db === $wsklad_version_database)
		{
			return false;
		}

		$charset_collate = wsklad()->database()->get_charset_collate();
		$table_name = wsklad()->database()->base_prefix . 'wsklad_accounts';
		$table_name_meta = $table_name . '_meta';

		$sql = "CREATE TABLE $table_name (
		`account_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
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
		`moysklad_login` VARCHAR(50) NULL DEFAULT NULL,
		`moysklad_password` VARCHAR(50) NULL DEFAULT NULL,
		`moysklad_token` VARCHAR(50) NULL DEFAULT NULL,
		`moysklad_role` VARCHAR(50) NULL DEFAULT NULL,
		`moysklad_tariff` VARCHAR(50) NULL DEFAULT NULL,
		`moysklad_account_id` VARCHAR(50) NULL DEFAULT NULL,
		PRIMARY KEY (`account_id`),
		UNIQUE INDEX `account_id` (`account_id`)
		) $charset_collate;";

		$sql_meta = "CREATE TABLE $table_name_meta (
		`meta_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
		`account_id` BIGINT(20) NULL DEFAULT NULL,
		`name` VARCHAR(90) NULL DEFAULT NULL,
		`value` LONGTEXT NULL DEFAULT NULL,
		PRIMARY KEY (`meta_id`),
		UNIQUE INDEX `meta_id` (`meta_id`)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		dbDelta($sql);
		dbDelta($sql_meta);

		add_site_option('wsklad_version_database', $wsklad_version_database);

		return true;
	}
}