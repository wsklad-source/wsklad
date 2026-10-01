<?php
/**
 * What the release actually ships under `assets/`, and whether it adds up.
 *
 * Three things are checked, and all three were broken in this tree before the check
 * existed:
 *
 *   1. A shipped stylesheet or script that points at a source map with
 *      `sourceMappingURL`, where that map is not itself shipped. The browser asks for
 *      it on every admin page load and gets a 404. Both `main.css` and
 *      `bootstrap.bundle.min.js` did this. It is not a bug anyone finds by reading
 *      the code: the reference is a trailing comment, and the 404 lands in a console
 *      nobody opens.
 *   2. A file under `assets/` that is never enqueued. `tocbot.js` — 45 KB of the
 *      unminified library, sitting next to the `tocbot.min.js` that is what actually
 *      loads — was downloaded by every install and parsed by nobody.
 *   3. A script enqueued in `<head>`. `admin.js` calls `bootstrap.Popover()` and
 *      `tocbot.init()` on `DOMContentLoaded`, so it also has to be ordered after both
 *      libraries, and that ordering is declared rather than incidental.
 *
 * The map check reads `.distignore` instead of hard-coding exclusions, because the
 * question is not "is this map in the repository" but "is this map in the ZIP", and
 * those are different files with different fates.
 */
$root = rtrim(str_replace('\\', '/', $argv[1] ?? ''), '/');
if ($root === '') { $root = str_replace('\\', '/', dirname(__DIR__)); }
$root .= '/';

$assets = $root . 'assets/';
if (!is_dir($assets)) { fwrite(STDERR, "not a plugin root: $root\n"); exit(2); }

/**
 * The `.distignore` paths that would drop something from the release.
 *
 * Read the way the release builder reads the file: one path per line, a leading slash
 * means "from the plugin root", a bare name or glob means "anywhere". Blank lines and
 * comments are not paths.
 *
 * ⚠ The builder also has exclusions of its own that are not in this file, and this gate
 * cannot see them. That is why every rule relied on here is written out in `.distignore`
 * even when the builder would have applied it anyway: a rule this gate cannot read is a
 * rule this gate cannot check, and `*.map` is exactly such a rule until it is declared.
 */
$excluded = [];
foreach (preg_split("/\r\n|\n|\r/", (string) file_get_contents($root . '.distignore')) as $line) {
	$line = trim($line);
	if ('' === $line || '#' === $line[0]) { continue; }
	$excluded[] = $line;
}

/**
 * Would this plugin-root-relative path reach the ZIP?
 *
 * This mirrors the release builder's matching as it actually behaves, not as its own
 * docblock describes it. The builder computes `anchored` with a leading-slash test that
 * runs *after* the slashes have been stripped, so `anchored` is always false: a plain
 * path rule matches only when some path segment equals it exactly, and a rooted path
 * rule matches nothing at all.
 *
 * That is why the rule in `.distignore` has to be written as a glob rather than as a
 * path. Do not "tidy" this function into the documented behaviour: it would disagree with
 * the builder, and a gate that disagrees with the thing it checks is worse than no gate,
 * because it reports green.
 *
 * ⚠ The glob itself is deliberately not written inside this comment. A block comment is
 * closed by the first `*` followed by `/`, and every glob of that shape contains one —
 * so quoting the rule here turns the rest of this docblock into code, and the file stops
 * parsing. It is spelled out on the `//` line inside the function instead.
 */
function shipped(string $relative, array $excluded): bool
{
	foreach ($excluded as $pattern) {
		$value = trim(preg_replace('~\\\\~', '/', $pattern), '/');

		if ('' === $value) { continue; }

		$wildcard = strpbrk($value, '*?[') !== false;

		if ($wildcard) {
			if (fnmatch($value, $relative) || fnmatch($value, basename($relative))) { return false; }
			continue;
		}

		// The builder's `in_array($value, $segments)` check, which is what a plain path
		// rule actually falls through to. The rule that keeps the unminified tocbot build
		// out of the ZIP has to be a glob for this reason:
		//     assets/<star>/tocbot/tocbot.js
		// with the star spelled out, because the real one would close this comment.
		if (in_array($value, explode('/', trim($relative, '/')), true)) { return false; }
	}

	return true;
}

$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($assets, FilesystemIterator::SKIP_DOTS));

foreach ($it as $f) {
	if (!$f->isFile()) { continue; }
	$relative = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen($root))), '/');
	$files[$relative] = filesize($f->getPathname());
}

ksort($files);

$pass = 0; $fail = 0;
function ok(string $l, bool $c, string $d = ''): void
{
	global $pass, $fail;
	$c ? $pass++ : $fail++;
	echo ($c ? '  PASS  ' : '  FAIL  ') . $l . ($c || $d === '' ? '' : ' - ' . $d) . "\n";
}

// The enqueued asset list is read out of the source rather than listed here, so this
// check cannot pass by agreeing with a stale copy of itself. `array_unique` because the
// report below is a list of assets, not a list of occurrences of the string in a file.
$admin = (string) file_get_contents($root . 'src/Admin.php');
$enqueued = [];

foreach (['css', 'js'] as $kind) {
	if (!preg_match_all("~'assets/([A-Za-z0-9_./-]+\." . $kind . ")'~", $admin, $m)) { continue; }
	foreach ($m[1] as $url) { $enqueued[strtolower($url)] = true; }
}

echo "=== no shipped file points at a source map it does not ship ===\n";

$dangling = [];

foreach ($files as $relative => $_) {
	if (!preg_match('~\.(css|js)$~i', $relative)) { continue; }

	// Only files that actually ship. A `sourceMappingURL` inside a file the ZIP does not
	// contain cannot produce a 404 for anybody: the browser never asks for that file, so
	// it never reads the comment. Judging it anyway produces a failure nobody can fix
	// without either shipping dead weight or editing vendored source for no reason.
	if (!shipped($relative, $excluded)) { continue; }

	$text = (string) file_get_contents($root . $relative);

	// Matched in a loop rather than with `preg_match_all()`: with a single capture group
	// that function collapses its result to a string, which is right at runtime and
	// ambiguous to a static analyser.
	$offset = 0;
	while (preg_match('~sourceMappingURL=([^\s*]+)~', $text, $m, 0, $offset)) {
		$map = (string) $m[1];
		$offset += strlen($m[0]);

		if (shipped(dirname($relative) . '/' . $map, $excluded)) { continue; }

		$dangling[$relative . ' -> ' . $map] = $relative . ' -> ' . $map;
	}
}

ok('no dangling sourceMappingURL', [] === $dangling, implode(', ', $dangling));

echo "\n=== the release carries only files that are loaded ===\n";

ok('the enqueued asset list is readable out of Admin.php', [] !== $enqueued);

$orphans = [];

foreach ($files as $relative => $size) {
	if (!preg_match('~^assets/(.+\.(?:css|js))$~i', $relative, $m)) { continue; }
	if (!shipped($relative, $excluded)) { continue; }

	if (isset($enqueued[strtolower($m[1])])) { continue; }

	$orphans[] = $relative;
}

ok('every shipped .css/.js is enqueued', [] === $orphans, implode(', ', $orphans));

echo "\n=== scripts do not block the header ===\n";

/**
 * One `wp_enqueue_script()` call, as its positional arguments.
 *
 * Split on commas at nesting depth zero rather than matched with a pattern, so the
 * result does not depend on how the call happens to be laid out across lines.
 */
$enqueueCalls = function (string $php): array {
	$out = [];
	$offset = 0;

	while (false !== ($start = strpos($php, 'wp_enqueue_script', $offset))) {
		$open = strpos($php, '(', $start);
		if (false === $open) { break; }

		$depth = 0;
		$i = $open;
		for (; $i < strlen($php); $i++) {
			if ('(' === $php[$i]) { $depth++; }
			if (')' === $php[$i]) { $depth--; if (0 === $depth) { break; } }
		}

		$body = substr($php, $open + 1, $i - $open - 1);

		$args = [];
		$depth = 0;
		$buf = '';
		for ($j = 0; $j < strlen($body); $j++) {
			$c = $body[$j];
			if ('(' === $c || '[' === $c) { $depth++; }
			if (')' === $c || ']' === $c) { $depth--; }

			if (',' === $c && 0 === $depth) { $args[] = trim($buf); $buf = ''; continue; }

			$buf .= $c;
		}
		$args[] = trim($buf);

		$out[] = $args;
		$offset = $i;
	}

	return $out;
};

$calls = $enqueueCalls($admin);

$inHead = [];
$depsByHandle = [];

foreach ($calls as $args) {
	$handle = trim((string) ($args[0] ?? ''), " \t\n\r'\"");
	if ('' === $handle) { continue; }

	// `wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer )` — a missing fifth
	// argument means the header, which is the default this check exists to catch.
	$inFooter = isset($args[4]) && 'true' === strtolower(trim($args[4], " \t\n\r'\""));

	if (!$inFooter) { $inHead[] = $handle; }

	$depsByHandle[$handle] = $args[2] ?? '';
}

ok('the plugin enqueues at least one script', [] !== $calls, count($calls) . ' calls');
ok('every enqueued script asks for the footer', [] === $inHead, implode(', ', $inHead));

$mainDeps = $depsByHandle['wsklad_admin_main'] ?? '';
ok(
	'admin.js declares the libraries it calls',
	false !== strpos($mainDeps, 'wsklad_admin_bootstrap') && false !== strpos($mainDeps, 'wsklad_admin_tocbot'),
	$mainDeps
);

echo "\n=== what one admin page loads ===\n";

$loaded = 0;
$dead = 0;
$blocking = 0;

foreach ($files as $relative => $size) {
	if (!preg_match('~^assets/(.+\.(?:css|js))$~i', $relative, $m)) { continue; }
	if (!shipped($relative, $excluded)) { continue; }

	if (!isset($enqueued[strtolower($m[1])])) { $dead += $size; continue; }

	// Stylesheets belong in the head: moving one would make the page paint unstyled.
	// Scripts do not, and `in_footer` is checked above.
	$isCss = '.css' === substr($m[1], -4);
	$where = $isCss ? 'head' : 'footer';

	if ($isCss) { $blocking += $size; }

	printf("  %-44s %8d  %s\n", $m[1], $size, $where);
	$loaded += $size;
}

printf("  %-44s %8s\n", str_repeat('-', 44), str_repeat('-', 8));
printf("  %-44s %8d\n", 'total loaded', $loaded);
printf("  %-44s %8d  blocks first paint\n", 'of that, render-blocking', $blocking);
printf("  %-44s %8d  moved out of the head\n", 'of that, deferred to the footer', $loaded - $blocking);
printf("  %-44s %8d  shipped, loaded by nobody\n", 'dead weight', $dead);

ok('nothing is shipped that nobody loads', 0 === $dead, $dead . ' bytes');

printf("\nPASS: %d  FAIL: %d\n", $pass, $fail);
exit($fail > 0 ? 1 : 0);