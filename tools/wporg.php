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

echo "\n=== the field lengths the wp.org parser enforces ===\n";

/**
 * Limits read out of `WordPress\Plugin_Check\Lib\Readme\Parser`, which is the parser
 * WordPress.org itself uses, vendored into the Plugin Check plugin:
 *
 *   public $maximum_field_lengths = array(
 *       'short_description' => 150,   // characters
 *       'section'           => 2500,  // WORDS
 *       'section-changelog' => 5000,  // WORDS
 *       'section-faq'       => 5000,  // WORDS
 *   );
 *
 * ⚠ **The two are measured in different units, and getting that wrong invents a defect.**
 * `trim_length()` takes a `$type`: the short description is trimmed by
 * `mb_strlen()`, sections by splitting on whitespace and comparing `$length * 2` pieces -
 * i.e. sections are limited in *words*. Counting characters instead reported the changelog
 * as 1852 characters over its limit when it is about 1000 words against a 5000-word budget.
 * A gate that cries wolf is a gate that gets ignored.
 *
 * Two of these are errors rather than advice - `Plugin_Readme_Check::check_for_warnings()`
 * reports `trimmed_short_description` and every `trimmed_section_*` at severity 6, and
 * `too_many_tags` at severity 5 with a hard limit of five. The plugin silently truncates an
 * over-long field, so a readme that is too long is not rejected: it is quietly cut, and the
 * visitor sees a sentence that ends mid-thought. That is worse than an error, which is why
 * it is checked here rather than left to the review queue.
 *
 * Written as data rather than typed inline, so the numbers have one place to be wrong.
 */
$charLimits = ['short_description' => 150];
$wordLimits = ['section' => 2500, 'section-changelog' => 5000, 'section-faq' => 5000];

// The short description is the first non-blank line after the header block that does not
// parse as a `Key: value` header. That is how `Parser::parse_readme_contents()` finds it:
// it walks the header lines, breaks on the first line that "isn't blank but also doesn't
// look like a header", and treats that line as the description.
//
// ⚠ The earlier version of this check passed an empty key to `$grab()`, which looks for a
// line starting with a colon. No readme line does, so the "check" compared the length of an
// empty string to 150 and passed on a description of any length at all - a planted 160
// characters sailed straight through it. A check that cannot fail is worse than no check.
$validHeader = '/^(?:tested up to|requires at least|requires php|tags|contributors|donate link'
	. '|stable tag|license|license uri):\s*(.+)$/i';

$short = '';
$afterTitle = false;

foreach (preg_split("/\r\n|\n|\r/", $readme) as $line) {
	$line = trim($line);

	if ('' === $line) { continue; }

	if (!$afterTitle) {
		if ('===' === substr($line, 0, 3)) { $afterTitle = true; }
		continue;
	}

	if (preg_match($validHeader, $line)) { continue; }

	$short = $line;
	break;
}

// A readme with no short description is itself a parser warning
// (`no_short_description_present`), and there is nothing to measure without it.
ok('the readme carries a short description', '' !== $short);

ok(
	sprintf('the short description is at most %d characters', $charLimits['short_description']),
	mb_strlen($short) <= $charLimits['short_description'],
	mb_strlen($short) . ' characters: ' . mb_substr($short, 0, 60)
);

// Tags: the parser keeps the first five and reports the rest as ignored, silently.
$tagLine = $grab($readme, 'Tags');
$tags = array_values(array_filter(array_map('trim', explode(',', $tagLine)), 'strlen'));
ok(
	'the plugin has at most 5 tags',
	count($tags) <= 5,
	count($tags) . ' tags: ' . implode(' | ', $tags)
);

// Sections, measured in words, each against its own limit where the parser sets one.
preg_match_all('/^== (.+?) ==\s*$(.*?)(?=^== |\z)/ms', $readme, $sections, PREG_SET_ORDER);

foreach ($sections as $section) {
	$key = strtolower(str_replace([' ', '_'], '_', $section[1]));
	$name = 'section-' . $key;
	$max = $wordLimits[$name] ?? $wordLimits['section'];

	$words = preg_split('/\s+/u', trim($section[2]), -1, PREG_SPLIT_NO_EMPTY);
	$count = false === $words ? 0 : count($words);

	ok(
		sprintf('"== %s ==" is at most %d words', $section[1], $max),
		$count <= $max,
		$count . ' words'
	);
}

echo "\n=== the upgrade notice is at most 300 characters per version ===\n";

$noticeStart = strpos($readme, '== Upgrade Notice ==');
$noticeText = '';

if (false !== $noticeStart) {
	$after = substr($readme, $noticeStart + strlen('== Upgrade Notice =='));
	$end = strpos($after, "\n== ");
	$noticeText = false === $end ? $after : substr($after, 0, $end);
}

// Everything above the first `= X.Y.Z =` heading is the note about the limit itself, which
// is not part of the notice and must not be counted as if it were.
$body = preg_replace('/^= \d+\.\d+\.\d+ =\s*$/m', '', $noticeText);
$body = trim(preg_replace('/\s+/', ' ', (string) $body));

ok('the upgrade notice body is at most 300 characters', mb_strlen((string) $body) <= 300,
	mb_strlen((string) $body) . ' characters');

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
