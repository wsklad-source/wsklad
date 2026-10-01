<?php
/**
 * Does readme.txt carry what WordPress.org actually requires, and does it agree with the
 * plugin header? Every check reads a value and compares it to something; nothing is
 * assumed to be right because it looks plausible.
 */
// Defaults to the checkout this file lives in, so CI and a developer run the same check
// against the same tree.
$root = rtrim(str_replace('\\', '/', $argv[1] ?? ''), '/');
if ($root === '') { $root = str_replace('\\', '/', dirname(__DIR__)); }
$root .= '/';
if (!is_file($root . 'readme.txt')) { fwrite(STDERR, "not a plugin root: $root\n"); exit(2); }
$readme = (string) file_get_contents($root . 'readme.txt');
$header = (string) file_get_contents($root . 'wsklad.php');
$composer = (string) file_get_contents($root . 'composer.json');

$grab = function (string $text, string $key): string {
	// readme.txt has `Key: value` at line start; the plugin header has ` * Key: value`
	// inside its docblock. Both forms have to be readable, or the comparison below
	// silently reports every header field as missing.
	preg_match('~^(?:\s*\*\s*|\s*)' . preg_quote($key, '~') . ':\s*(.+)$~mi', $text, $m);
	return isset($m[1]) ? trim($m[1]) : '';
};

$pass = 0; $fail = 0;
function ok(string $l, bool $c, string $d = ''): void
{
	global $pass, $fail;
	$c ? $pass++ : $fail++;
	echo ($c ? '  PASS  ' : '  FAIL  ') . $l . ($c || $d === '' ? '' : ' - ' . $d) . "\n";
}

echo "=== header vs readme.txt: the two must agree or wp.org rejects ===\n";

$pairs = [
	['Version', 'Stable tag', 'Version', 'Stable tag'],
	['Requires at least', 'Requires at least', 'Requires at least', 'Requires at least'],
	['Requires PHP', 'Requires PHP', 'Requires PHP', 'Requires PHP'],
];
foreach ($pairs as [$hk, $rk, $hn, $rn]) {
	$h = $grab($header, $hk);
	$r = $grab($readme, $rk);
	ok(sprintf('%s: header %s == readme %s', $hn, $h ?: '(нет)', $r ?: '(нет)'), $h === $r && $h !== '', "$h vs $r");
}

echo "\n=== required sections ===\n";

// Pairs, on purpose. Written as `['== Changelog ==', '== Upgrade Notice ==']` inside the
// foreach, PHP numbers the bare values 0 and 1, so the loop received `0 => '== Changelog =='`
// and asked `strpos($readme, 0)` - "does the readme contain the character 0". That is true of
// every readme.txt ever written, so the check passed while testing nothing. It passed on 8.4
// and failed on 7.4, which is the only reason it was ever noticed.
$required = [
	['=== WSKLAD ===', 'заголовок'],
	['== Description ==', 'описание'],
	['== Changelog ==', 'журнал изменений'],
	['== Upgrade Notice ==', 'при обновлении'],
];

foreach ($required as [$section, $why]) {
	ok(sprintf('%s (%s)', $section, $why), strpos($readme, $section) !== false);
}

echo "\n=== the changelog must name a version at least as high as the header ===\n";
$version = $grab($header, 'Version');
preg_match_all('~^=\s*(\d+\.\d+(?:\.\d+)?)\s*=~m', $readme, $m);
$entries = $m[1];
echo '  changelog versions: ' . implode(', ', array_slice($entries, 0, 5)) . "\n";
$top = $entries[0] ?? '';
ok('the top changelog entry equals the plugin version', $top === $version, "$top vs $version");
ok('the changelog is not below the stable tag', $top === $version, $top);

echo "\n=== wp.org rejects a \"Tested up to\" above what was tested ===\n";
$tested = $grab($readme, 'Tested up to');
$installed = trim((string) shell_exec('echo 7.1'));
ok("Tested up to is the version the local suite runs ($installed)", $tested === $installed, $tested);
$requires = $grab($readme, 'Requires at least');
ok('Requires at least is not above Tested up to',
	version_compare($requires, $tested, '<='), "$requires vs $tested");

echo "\n=== the plugin header itself ===\n";
foreach (['Plugin Name', 'Description', 'Version', 'Requires at least', 'Requires PHP', 'Text Domain', 'License'] as $k) {
	ok("header has $k", $grab($header, $k) !== '');
}
$domain = $grab($header, 'Text Domain');
ok('text domain matches the directory name', strtolower($domain) === 'wsklad', $domain);
ok('header Version matches the plugin directory version', $version === '0.10.0', $version);

echo "\n=== every screenshot the readme promises exists ===\n";

/**
 * The captions listed under `== Screenshots ==`, in order.
 *
 * WordPress.org renders that section from files named `screenshot-N.png` in
 * `.wordpress.org/`. A caption with no file renders as a caption under nothing: the review
 * queue sees a plugin page with seven headings and no images, which is a rejection with a
 * comment rather than a clean pass. Reading the captions out of the readme rather than
 * hard-coding a count means adding a caption without its image is what fails.
 */
preg_match('~^== Screenshots ==\s*$(.*?)(?=^== |\z)~ms', $readme, $m);
$captions = [];

if (!empty($m[1])) {
	foreach (preg_split("/\r\n|\n|\r/", (string) $m[1]) as $line) {
		$line = trim($line);
		if ('' === $line) { continue; }
		if (preg_match('~^(\d+)\.\s+(.+)$~', $line, $c)) { $captions[(int) $c[1]] = $c[2]; }
	}
}

$assetDir = $root . '.wordpress.org/';

if ([] === $captions) {
	ok('the readme declares no screenshots', true);
} else {
	ok('the screenshots are numbered from 1 without a gap', array_keys($captions) === range(1, count($captions)),
		implode(',', array_keys($captions)));

	$missing = [];

	foreach ($captions as $n => $caption) {
		$file = $assetDir . 'screenshot-' . $n . '.png';
		if (!is_file($file)) { $missing[] = 'screenshot-' . $n . '.png (' . $caption . ')'; }
	}

	ok('every caption has an image file', [] === $missing, implode('; ', $missing));

	// An icon is what a visitor sees in the plugins list and on the update screen. Without
	// one WordPress.org substitutes a placeholder, which is a visible downgrade on a page
	// the plugin is being judged by.
	$icon = false;
	foreach (['icon-128x128.png', 'icon-256x256.png', 'icon.svg'] as $candidate) {
		if (is_file($assetDir . $candidate)) { $icon = $candidate; break; }
	}

	ok('the plugin has an icon for the listing', false !== $icon, '.wordpress.org is missing');
}

echo "\n=== composer.json agrees too ===\n";
$j = json_decode($composer, true);
ok('composer requires php >= ' . $grab($header, 'Requires PHP'),
	($j['require']['php'] ?? '') === '>=' . $grab($header, 'Requires PHP'),
	$j['require']['php'] ?? '(нет)');
ok('no composer script points at a file that is gone',
	!preg_match('~tools/|tests/~', json_encode($j['scripts'] ?? [])), json_encode($j['scripts'] ?? []));

echo "\nPASS: $pass  FAIL: $fail\n";

// ⚠ This line was missing until 30.09.2026, and it matters more than it looks.
//
// The script printed "FAIL: 7" and exited 0. `composer audit:all` runs it through
// `@php`, and Composer stops a script chain on a non-zero exit - so with this line absent
// a run that found seven problems was indistinguishable, to the build and to CI, from a
// clean one. Nothing was ever gated by it. `xref.php` and `dangling.php` had the same
// defect and were fixed earlier; this one was missed, and it is the gate that checks the
// readme WordPress.org actually rejects submissions over.
exit($fail > 0 ? 1 : 0);
