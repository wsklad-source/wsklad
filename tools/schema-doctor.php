<?php
/**
 * Report whether the database this plugin is talking to is the database it expects.
 *
 * `Schema::tablesExist()` asks whether the tables are there. This asks whether they are the
 * tables the code will run against: the declared columns, at the declared types, with the
 * declared indexes. The difference matters because a table that has lost a column still exists,
 * still passes the version check, and still skips the self-heal — and the first symptom is a
 * MySQL error in the middle of whatever the site owner was doing at the time.
 *
 * Two things it deliberately does not do. It does not repair anything: this is a report, and a
 * tool that silently changes a production schema is a different tool with a different name. And
 * it does not compare the whole `SHOW CREATE TABLE` output, because MySQL 8.0.19 dropped display
 * widths from the output, so an exact comparison reports a mismatch on every freshly created
 * table and teaches the reader to ignore it.
 *
 * ⚠ Needs a WordPress. The schema is only knowable from a live connection, so this cannot run in
 * a CI job without a database — unlike the other gates in this directory, which read files. The
 * integration harness covers it instead, by dropping a column and asking for the report again.
 *
 * @package Wsklad\Tools
 */

$root = rtrim(str_replace('\\', '/', $argv[1] ?? ''), '/');

if ($root === '') {
	$root = str_replace('\\', '/', dirname(__DIR__));
}

$wpPath = getenv('WSKLAD_WP_PATH') ?: 'C:/Users/FRESCO~1/AppData/Local/Temp/opencode/mysql/wordpress/';
$wpFile = rtrim(str_replace('\\', '/', $wpPath), '/') . '/wp-load.php';

if (!is_file($wpFile)) {
	fwrite(STDERR, "No WordPress at {$wpFile}\nSet WSKLAD_WP_PATH, or WSKLAD_WP_SKIP=1 for the checks that need no database.\n");

	exit(2);
}

define('WP_USE_THEMES', false);

require_once $wpFile;
require_once $root . '/wsklad.php';

$schema = wsklad()->schema();

global $wpdb;

printf("plugin  : %s\n", defined('WSKLAD_VERSION') ? WSKLAD_VERSION : '(unknown)');
printf("version : declared %d, installed %d\n", $schema::VERSION, $schema->getVersion());
printf("host    : %s\n", DB_HOST);
printf("tables  : %s\n\n", implode(', ', array_map(
	static fn(string $t): string => str_replace((string) $wpdb->base_prefix, '', $t),
	$schema->getTables()
)));

$report = $schema->inspect();

echo "=== integrity ===\n";

foreach ($report['columns_mistyped'] as $table => $columns) {
	foreach ($columns as $name => $detail) {
		printf("  [TYPE ] %s.%s is %s, expected %s\n", $table, $name, $detail['actual'], $detail['expected']);
	}
}

foreach ($report['tables_missing'] as $table) {
	printf("  [TABLE] %s does not exist\n", $table);
}

foreach ($report['columns_missing'] as $table => $columns) {
	printf("  [COL  ] %s is missing: %s\n", $table, implode(', ', $columns));
}

foreach ($report['indexes_missing'] as $table => $indexes) {
	printf("  [INDEX] %s is missing: %s\n", $table, implode(', ', $indexes));
}

if ($report['orphans'] > 0) {
	printf("  [ORPH ] %d metadata row(s) point at an account that no longer exists\n", $report['orphans']);
}

if ($report['ok'] && 0 === $report['orphans']) {
	echo "  everything the release expects is there\n";
}

printf("\nRESULT: %s\n", $report['ok'] ? 'OK' : 'PROBLEMS FOUND');

// Non-zero when something is wrong, so this can be a gate rather than a note. A report that
// always exits 0 is a report nobody reads.
exit($report['ok'] ? 0 : 1);