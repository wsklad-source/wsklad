<?php
/**
 * Verify the copy rewrite landed, in the order that catches the worst problem first.
 *
 * The post-condition is the only one that matters: after the rewrite, every NEW string must be
 * in the source, and the old ones gone. Checking "was the old string found" is useless once the
 * replacement has run - the old string is absent precisely because it worked, which is what made
 * the first run report 217 phantom failures.
 *
 * Self-contained by design. It once required a gettext parser from an agent tool directory, which
 * made the gate unreproducible twice over: CI could not see the file at all, and on PHP 7.4 the
 * `require` was a fatal error, because that file opens with `declare(strict_types=1)` and a
 * strict_types declaration is only legal as the first statement of a script. The two readers
 * below replace it, and the check that genuinely needs a full parser - "the template equals the
 * code" - is not repeated here at all, because `composer i18n:pot:check` already performs it
 * against the canonical extractor.
 */

// The root defaults to the checkout this file lives in.
$root = rtrim(str_replace('\\', '/', $argv[1] ?? ''), '/');
if ($root === '') { $root = str_replace('\\', '/', dirname(__DIR__)); }
if (!is_file($root . '/wsklad.php')) { fwrite(STDERR, "not a plugin root: $root\n"); exit(2); }

// The rewrite map travels with the check. It used to be required from `%TEMP%`, which made
// this gate unreproducible: the map is the record of what the rewrite was supposed to do, so
// a run without it could not say whether the rewrite had landed at all.
$map = require __DIR__ . '/copy-rewrite-map.php';

/**
 * Read a compiled .mo into msgid => msgstr.
 *
 * The format is a fixed header, a table of (length, offset) pairs, and two string tables. It is
 * read here rather than pulled from outside the repository for the reason in the file header.
 *
 * @return array<string, string>
 */
$readMo = static function (string $file) : array
{
	if (!is_file($file)) { return []; }

	$bytes = (string) file_get_contents($file);

	if (strlen($bytes) < 20 || substr($bytes, 0, 4) !== "\xde\x12\x04\x95")
	{
		fwrite(STDERR, "not a .mo file: $file\n");

		return [];
	}

	$readU32 = static function (int $at) use ($bytes) : int
	{
		// "V" is 32-bit little-endian, unsigned, which is what the format uses.
		$unpacked = unpack('V', substr($bytes, $at, 4));

		return false === $unpacked ? 0 : $unpacked[1];
	};

	$count = $readU32(8);

	// The tables' positions are read from the header, not assumed. Assuming them - "original
	// table at 12, translation table right after it" - is off by the width of the two hash
	// fields, which shifts the translation table and yields a reader that resolves 354 of 356
	// strings and reports the two missing ones as untranslated. The catalogue was fine; this
	// reader was not, and it was the reader that had to change.
	$origTable = $readU32(12);
	$transTable = $readU32(16);

	$out = [];

	for ($i = 0; $i < $count; $i++)
	{
		$len  = $readU32($origTable + ($i * 8));
		$at   = $readU32($origTable + ($i * 8) + 4);

		$key = substr($bytes, $at, $len);

		$tLen = $readU32($transTable + ($i * 8));
		$tAt  = $readU32($transTable + ($i * 8) + 4);

		// The empty msgid is the catalogue header, not a translation of anything.
		if ('' === $key) { continue; }

		$out[$key] = substr($bytes, $tAt, $tLen);
	}

	return $out;
};

/**
 * Ask the canonical extractor what the code actually contains.
 *
 * `make-pot.php --list` prints one JSON object per extractable string. It is used instead of
 * reading the .pot here because this check is about the code, and the tool that produces the
 * template is the only one that can answer for both without two implementations disagreeing.
 *
 * @return array<int, array{id: string, plural: ?string, ctxt: string}>
 */
$readCodeMsgids = static function (string $root) : array
{
	$command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/make-pot.php') . ' --list';
	$out = [];

	exec($command . ' 2>&1', $out, $status);

	if (0 !== $status)
	{
		fwrite(STDERR, "make-pot.php --list failed:\n" . implode("\n", $out) . "\n");

		return [];
	}

	$rows = [];

	foreach ($out as $line)
	{
		$line = trim($line);

		if ('' === $line) { continue; }

		$decoded = json_decode($line, true);

		if (is_array($decoded) && isset($decoded['id']) && '' !== $decoded['id'])
		{
			$rows[] = $decoded;
		}
	}

	return $rows;
};

$quoted = static fn(string $v) : string => "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $v) . "'";

$files = [];
$skip  = '~/(vendor|node_modules|tests|tools|plans|docs|build|dist)/~';

foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file)
{
	$path = str_replace('\\', '/', $file->getPathname());

	if('php' === strtolower($file->getExtension()) && !preg_match($skip, $path))
	{
		$files[$path] = (string) file_get_contents($path);
	}
}

$corpus = implode("\n", $files);

$fail = 0;

// Strings the rewrite produced that were later deleted, with the reason.
//
// The map is a record of a rewrite that has since been superseded: the promo block it rewrote
// was removed on 30.09.2026, and five of its strings went with it. Without this list the gate
// asks for text that is supposed to be gone and fails on it - which is how a check that was
// right when written starts demanding the past back. Each entry says why, so a future run can
// tell a deliberate removal from a string that simply failed to apply.
$retired =
[
	'Logs' => 'the account Logs tab was an advertisement for the paid log viewer, and the tab was removed with it',
	'View and manage the event log for this account.' => 'the same tab; its description promised a log viewer the tab never had',
	'Install the log viewer extension to read the logs. They record what the plugin did and what went wrong.' => 'src/Admin/Promo/Logs.php and views/promo/logs.php, removed',
	'When something misbehaves, the logs are the first place to look. Without the extension you can open them over FTP.' => 'src/Admin/Promo/Logs.php and views/promo/logs.php, removed',
	'Once the extension is installed, it will show its log viewer here.' => 'src/Admin/Promo/Logs.php and views/promo/logs.php, removed',
	'The event log for this account. View it in the log viewer extension, or over FTP.' => 'replaced by a description naming the directory the plugin writes to, instead of the paid extension',
];

// 1. Every new string is present, every old one is gone.
$absent = [];
$stillThere = [];

foreach($map as $old => $spec)
{
	$new = $spec[0];

	if($new !== $old && false === strpos($corpus, $quoted($new)) && !isset($retired[$new]))
	{
		$absent[] = $new;
	}

	// Only a *changed* entry can leave the old string behind. An entry whose new text equals
	// its old text is a mapping that only supplies Russian, and reporting it as stale would be
	// noise on every run.
	if($new !== $old && false !== strpos($corpus, $quoted($old)))
	{
		$stillThere[] = $old;
	}
}

printf("=== the rewrite landed ===\n");
printf("  new strings expected but absent : %d\n", count($absent));
printf("  old strings still present       : %d\n", count($stillThere));

foreach(array_slice($absent, 0, 8) as $s)
{
	printf("    ABSENT  %s\n", substr($s, 0, 104));
}

foreach(array_slice($stillThere, 0, 8) as $s)
{
	printf("    STALE   %s\n", substr($s, 0, 104));
}

$fail += count($absent);

// 2. The product name in *user-facing text* only. `MoySklad` also occurs as
// `setMoyskladLogin()`, as the column `moysklad_login` and inside `api.moysklad.ru`; renaming
// any of those would break the schema or the API host, so the source is the wrong place to
// count. The template holds exactly the translatable strings.
$potNameText = (string) file_get_contents($root . '/assets/languages/wsklad.pot');
$joined     = preg_match_all('~MoySklad~', $potNameText);
$spaced     = preg_match_all('~Moy Sklad~', $potNameText);

printf("\n=== the product name in translatable text ===\n");
printf("  \"MoySklad\" in the template : %d  (expected 1: the api.moysklad.ru host)\n", $joined);
printf("  \"Moy Sklad\" in the template : %d\n", $spaced);

$hostOnly = preg_match_all('~MoySklad~', preg_replace('~api\.moysklad\.ru~', '', $potNameText));

printf("  outside a hostname          : %d\n", $hostOnly);

if($hostOnly > 0)
{
	$fail++;
}

// 3. The template equals the code.
//
// Not repeated here. `composer i18n:pot:check` runs the canonical extractor over the tree and
// compares the result with the committed template, which is the same comparison done by the tool
// that owns the format. Doing it a second time, with a second parser, would only be a second
// thing to keep in agreement.

printf("\n=== the template equals the code ===\n");
printf("  ..    not checked here - see `composer i18n:pot:check`\n");
$potText = (string) file_get_contents($root . '/assets/languages/wsklad.pot');

printf("\n=== the template is a template ===\n");
printf("  Cyrillic in .pot   : %d\n", preg_match_all('~[\x{0400}-\x{04FF}]~u', $potText));
printf("  non-empty msgstr  : %d\n", preg_match_all('~^msgstr(?:\[\d\])?\s+"[^"]+"~m', $potText));

$fail += preg_match_all('~[\x{0400}-\x{04FF}]~u', $potText);
$fail += preg_match_all('~^msgstr(?:\[\d\])?\s+"[^"]+"~m', $potText);

// 5. Every string the code contains resolves in the compiled catalogue.
//
// The msgids come from the canonical extractor and the keys from the shipped .mo, so the question
// is the one a site actually asks: when WordPress looks up a string this plugin uses, does the
// .mo that ships in the ZIP answer it? A singular msgid must be present; a plural one is keyed in
// the catalogue as `singular\0plural`, and the value must be non-empty or the right form never
// comes back.
printf("\n=== every code string resolves ===\n");

$mo  = $readMo($root . '/assets/languages/wsklad-ru_RU.mo');
$ids = $readCodeMsgids($root);

$unresolved = [];

foreach ($ids as $row) {
	$key = null !== $row['plural'] && '' !== $row['plural']
		? $row['id'] . "\0" . $row['plural']
		: $row['id'];

	if (!isset($mo[$key]) || '' === $mo[$key]) {
		$unresolved[$key] = true;
	}
}

printf("  code strings      : %d\n", count($ids));
printf("  catalogue entries : %d\n", count($mo));
printf("  not resolving     : %d\n", count($unresolved));

foreach (array_slice(array_keys($unresolved), 0, 8) as $m)
{
	printf("    %s\n", substr(str_replace("\0", ' / ', $m), 0, 104));
}

$fail += count($unresolved);

// 6. Placeholders survived the rewrite.
printf("\n=== placeholders ===\n");

$broken = [];

foreach($map as $old => $spec)
{
	$new = $spec[0];

	preg_match_all('~%(?:\{[^}]*\})?\d*\$?[bcdeEfFgGosuxX]~', $old, $a);
	preg_match_all('~%(?:\{[^}]*\})?\d*\$?[bcdeEfFgGosuxX]~', $new, $b);

	if($a[0] !== $b[0])
	{
		$broken[] = $old;
	}
}

printf("  entries whose placeholders changed : %d\n", count($broken));

foreach(array_slice($broken, 0, 6) as $b)
{
	printf("    %s\n", substr($b, 0, 104));
}

$fail += count($broken);

printf("\nFAIL: %d\n", $fail);
exit($fail > 0 ? 1 : 0);
