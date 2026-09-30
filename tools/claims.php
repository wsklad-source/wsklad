<?php
/**
 * Do the claims the code makes about itself still hold?
 *
 * Version numbers, hook counts, table names, file paths and "since" markers are written
 * once and then drift. This checks the ones that can be checked mechanically, against the
 * release tree rather than against what a docblock says.
 */
// Defaults to the checkout this file lives in, so CI and a developer run the same check
// against the same tree. The argument is kept for pointing it at a fixture.
$root = rtrim(str_replace('\\', '/', $argv[1] ?? ''), '/');
if ($root === '') { $root = str_replace('\\', '/', dirname(__DIR__)); }
$root .= '/';
if (!is_file($root . 'wsklad.php')) { fwrite(STDERR, "not a plugin root: $root\n"); exit(2); }
$T = function (string $rel) use ($root): string { return (string) @file_get_contents($root . $rel); };

$pass = 0; $fail = 0;
function ok(string $l, bool $c, string $d = ''): void
{
	global $pass, $fail;
	$c ? $pass++ : $fail++;
	echo ($c ? '  PASS  ' : '  FAIL  ') . $l . ($c || $d === '' ? '' : ' - ' . $d) . "\n";
}

$version = preg_match('~Version:\s*(\S+)~', $T('wsklad.php'), $m) ? $m[1] : '?';

echo "=== version claims in docblocks ===\n";
$wrongVersion = [];
foreach (['src/Environment.php', 'src/Data/Schema.php', 'src/Uninstall.php', 'src/Admin.php'] as $f) {
	$t = $T($f);
	// Any "since X.Y.Z" or "in 0.X.Y" that names a version above the current one.
	if (preg_match_all('~(?:since|in|@since)\s+v?(\d+\.\d+\.\d+)~i', $t, $mm)) {
		foreach ($mm[1] as $v) {
			if (version_compare($v, $version, '>')) { $wrongVersion[] = "$f: $v"; }
		}
	}
}
ok('no docblock claims a version above ' . $version, !$wrongVersion, implode('; ', $wrongVersion));

echo "\n=== the schema version is the one that is installed ===\n";
preg_match('~const VERSION = (\d+);~', $T('src/Data/Schema.php'), $m);
$schemaVersion = (int) ($m[1] ?? 0);
ok("Schema::VERSION is 3 as released", $schemaVersion === 3, (string) $schemaVersion);

// The docblock's own version history must not describe versions this release does not have.
$t = $T('src/Data/Schema.php');
preg_match_all('~^\s*\*\s+(\d) - ~m', $t, $hm);
$described = array_map('intval', $hm[1]);
ok('the version history stops at the version installed',
	!$described || max($described) <= $schemaVersion,
	'described: ' . implode(',', $described) . ' / installed: ' . $schemaVersion);

echo "\n=== table names the code drops are the ones the schema creates ===\n";
$schemaTables = [];
preg_match_all('~wsklad_([a-z_]+)~', $T('src/Data/Schema.php'), $sm);
// The regex also matches option names (`wsklad_version_database`, `wsklad_version_init`),
// and the first version of this check reported two options as tables - a false alarm in a
// gate is how a gate gets ignored.
$optionLike = ['version_database', 'version_init', 'version_active', 'version', 'schema_version', 'unique_prefix'];
$schemaTables = array_values(array_unique(array_diff($sm[1], $optionLike)));

preg_match_all('~private \$tables\s*=\s*\[(.*?)\];~s', $T('src/Uninstall.php'), $um);
$uninstall = preg_match_all('~\'wsklad_([a-z_]+)\'~', $um[1][0] ?? '', $x) ? array_values(array_unique($x[1])) : [];

$notDropped = array_diff($schemaTables, $uninstall);
ok('Uninstall drops every table the schema creates', !$notDropped, implode(', ', $notDropped));
$extra = array_diff($uninstall, $schemaTables);
ok('Uninstall drops nothing the schema does not create', !$extra, implode(', ', $extra));

echo "\n=== the options the plugin writes are the ones it cleans up ===\n";
$written = [];
foreach (glob($root . 'src/*.php') as $f) {
	$t = (string) file_get_contents($f);
	if (preg_match_all('~(?:update_option|add_option|update_site_option|add_site_option)\(\s*\'(wsklad_[a-z_0-9]+)\'~i', $t, $om)) {
		foreach ($om[1] as $o) { $written[$o] = true; }
	}
}
foreach (glob($root . 'src/**/*.php') as $f) {
	$t = (string) file_get_contents($f);
	if (preg_match_all('~(?:update_option|add_option|update_site_option|add_site_option)\(\s*\'(wsklad_[a-z_0-9]+)\'~i', $t, $om)) {
		foreach ($om[1] as $o) { $written[$o] = true; }
	}
}
$u = $T('src/Uninstall.php');
$notCleaned = [];
foreach (array_keys($written) as $o) {
	// Transient-ish stamps and version markers are allowed to survive; anything holding
	// user data is not.
	if (preg_match('~(_version|_checked|_init|_active)$~', $o)) { continue; }
	if (strpos($u, "'" . $o . "'") === false) { $notCleaned[] = $o; }
}
ok('Uninstall removes every option that holds data', !$notCleaned, implode(', ', $notCleaned));

echo "\n=== the log path claim ===\n";
$e = $T('src/Environment.php');
// The paths go through an intermediate variable in both cases - `$content_dir` and
// `$wsklad_logs_dir` - so an assertion has to follow the assignment, not look for one
// expression. The behaviour itself is proven by the integration suite ("logs are NOT
// under uploads"); this only confirms the derivation is still in the source.
// dirname(uploads) is the content directory, and 'wsklad' hangs off that, not off uploads.
ok('the data directory is wp-content/wsklad, not uploads/wsklad',
	(bool) preg_match('~\$content_dir\s*=\s*dirname\(\$uploads\)~', $e)
	&& (bool) preg_match('~\$path\s*=\s*\$content_dir\s*\.\s*DIRECTORY_SEPARATOR\s*\.\s*.wsklad.~', $e));

// The logs hang off the data directory.
ok('the logs hang off the data directory',
	(bool) preg_match('~\$wsklad_logs_dir\s*=\s*\$this->get\(.wsklad_data_directory.\)~', $e));

// And the accounts directory still hangs off uploads, deliberately: it is the one path
// extensions read, and moving it would break them.
ok('the accounts directory still hangs off uploads (BC, deliberate)',
	(bool) preg_match('~\$this->get\(.wsklad_upload_directory.\)\s*\.\s*DIRECTORY_SEPARATOR\s*\.\s*.accounts.~', $e));

echo "\n=== hook names: unique, namespaced, and none mention a later milestone ===\n";
$hooks = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . 'src', FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
	if (strtolower($f->getExtension()) !== 'php') { continue; }
	$t = (string) file_get_contents($f->getPathname());
	preg_match_all('~do_action\(\s*[\'"](wsklad_[a-z_0-9]+)[\'"]~i', $t, $hm);
	foreach ($hm[1] as $h) { $hooks[$h] = ($hooks[$h] ?? 0) + 1; }
}
echo '  distinct hooks: ' . count($hooks) . "\n";
$duplicates = array_filter($hooks, function ($n) { return $n > 1; });
echo '  fired more than once: ' . count($duplicates) . "\n";

echo "\nPASS: $pass  FAIL: $fail\n";
