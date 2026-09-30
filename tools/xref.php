<?php
/**
 * Cross-reference every file path mentioned in the documentation against what exists.
 *
 * Cheap, and it finds the class of error that survives review: a document that points at
 * something removed three commits ago. Nothing here is guessed - a path is reported only
 * when it appears in the text and is not on disk.
 */
// The root defaults to the checkout this file lives in, so the gate means the same thing
// in CI, on another machine and in a different clone. The argument stays because the check
// is testable against a directory that is known to contain a broken reference - and a
// hardcoded path could only ever prove that the project happens to be clean, which is
// indistinguishable from a check that stopped looking.
$root = rtrim(str_replace('\\', '/', $argv[1] ?? ''), '/');
if ($root === '') { $root = str_replace('\\', '/', dirname(__DIR__)); }
if (!is_dir($root)) { fwrite(STDERR, "no such directory: $root\n"); exit(2); }
chdir($root);

$docs = [];
$it = new RecursiveIteratorIterator(
	new RecursiveCallbackFilterIterator(
		new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
		function ($f) {
			$p = str_replace('\\', '/', $f->getPathname());
			if (strpos($p, '/vendor/') !== false || strpos($p, '/.git/') !== false) { return false; }
			$e = strtolower($f->getExtension());
			return in_array($e, ['md', 'txt', 'json', 'yml', 'yaml', 'neon', 'dist', 'php'], true);
		}
	)
);
foreach ($it as $f) { $docs[] = $f->getPathname(); }
sort($docs);

// A path-looking token: optional directory + a filename with an extension we actually ship.
// The extension must be followed by a non-word character. Without that, the sniff code
// `WordPress.WP.AlternativeFunctions.json_encode_json_encode` matched as the path
// `WordPress.WP.AlternativeFunctions.json`, because `json` is a real extension and the
// rest of the code continues with an underscore.
$pattern = '~(?<![\w/.-])((?:[A-Za-z0-9_.-]+/)*[A-Za-z0-9_.-]+\.(?:md|txt|json|yml|neon|php|xml|dist|css|js|pot|po))(?![A-Za-z0-9_])~';

$knownMissing = [
	// Third-party and tooling paths that are not part of this repository by design.
	'vendor/autoload.php', 'vendor/bin/phpunit', 'composer.phar',
	'phpunit.xml.dist', 'phpstan.neon', 'phpcs.xml', 'phpunit.xml', // dev configs, deliberately out
];

/**
 * Paths named in prose rather than referenced as files. Each carries its reason, so a
 * future run can tell a genuinely dangling reference from a quotation.
 *
 * Without this, the check reported 9 findings that were all correct text, and a check that
 * always cries wolf is a check nobody runs.
 */
$quoted = [
	'CHANGELOG.md'      => 'AGENTS.md quotes the gitflow branch types `docs/*` and `changelog/*`; the file itself is gone',
	'SECURITY.md'       => 'AGENTS.md, same - quoted inside the branch-type table',
	'UPGRADE.md'        => 'AGENTS.md, same - quoted inside the branch-type table',
	'CONTRIBUTING.md'   => 'AGENTS.md, same - quoted inside the branch-type table',
	'class-accounts-storage.php' => 'phpcs.xml.dist quotes the file name a sniff demands, to say why it is excluded',
	'AccountsStorage.php'        => 'phpcs.xml.dist, same',
	'WordPress.WP.AlternativeFunctions.json_encode_json_encode' => 'phpcs.xml.dist - a sniff code, not a file',
	'build-wp-zip.php' => 'AGENTS.md names the release builder, which lives in the agent tool directory, not in this repository',
	'WordPress.WP.AlternativeFunctions' => 'phpcs.xml.dist - a sniff category in a <rule ref>, not a file',
	'wp-load.php' => 'AGENTS.md - a WordPress core file, referred to as such; it is not this repository\'s to contain',
	'PluginManager.php' => 'AGENTS.md quotes the Composer error text "In PluginManager.php line 821" - the file is inside composer\'s own phar',
	'config.platform.php' => 'AGENTS.md - a composer.json config key, not a file path',
	'Add.php' => 'AGENTS.md quotes three filenames as an example of a bad glob; they exist at src/Admin/Add.php, src/Privacy/Privacy.php and src/Traits/StoredDateTrait.php but the checker resolves bare names only against the root',
	'Privacy.php' => 'AGENTS.md, same',
	'StoredDateTrait.php' => 'AGENTS.md, same',
	'SUPPORTED_VERSIONS.md' => 'security.txt names the two files it replaces, which no longer exist - that is the point of the sentence',
];

$report = [];
foreach ($docs as $doc) {
	$text = (string) file_get_contents($doc);
	$rel = str_replace('\\', '/', substr($doc, strlen($root) + 1));
	if (!preg_match_all($pattern, $text, $m)) { continue; }

	$hits = [];
	foreach (array_unique($m[1]) as $p) {
		$p = trim($p, './');
		if ($p === '' || in_array($p, $knownMissing, true) || isset($quoted[$p])) { continue; }
		$candidates = [
			$root . '/' . $p,
			$root . '/' . basename($p),
			$root . '/docs/' . $p,
			$root . '/plans/' . $p,
			$root . '/src/' . $p,
			// `tools/` holds the release audit gates and the translation generator. Without
			// this, `AGENTS.md` naming `wporg.php` reads as a dangling reference the moment
			// the gate moves out of %TEMP% and into the repository - the check would have
			// failed on the very change that made it reproducible.
			$root . '/tools/' . $p,
		];
		$found = false;
		foreach ($candidates as $c) { if (is_file($c)) { $found = true; break; } }
		if (!$found) { $hits[] = $p; }
	}
	if ($hits) { $report[$rel] = $hits; }
}

ksort($report);
$total = 0;
foreach ($report as $doc => $hits) {
	echo "=== $doc ===\n";
	foreach ($hits as $h) { echo "   $h\n"; $total++; }
	echo "\n";
}
echo 'documents with dangling paths: ' . count($report) . "\n";
echo 'dangling path references: ' . $total . "\n";
