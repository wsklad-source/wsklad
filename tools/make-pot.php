<?php
/**
 * Build assets/languages/wsklad.pot from the code.
 *
 * Dependency-free on purpose: this runs in CI, where `composer install --no-dev` leaves no
 * autoloader worth relying on, and a template that can only be rebuilt by installing something
 * is a template that silently stops being maintained.
 *
 * Three rules, each learned from a defect rather than from a specification:
 *
 * 1. **Wrapping has to be lossless.** gettext concatenates the quoted parts of a long msgid
 *    verbatim. Cutting on a word boundary and dropping the space produces
 *    "…activate the plugin" + "to recreate them" == "…activate the pluginto recreate them",
 *    and the string silently stops resolving while the catalogue still reports it translated.
 *    Lines are therefore cut at an exact character offset.
 *
 * 2. **Two `msgid` lines is not a continuation.** It is a different key. The correct shape is
 *    `msgid ""` followed by quoted lines.
 *
 * 3. **The template carries no translation.** Every msgstr is empty. A .pot is what every other
 *    locale is generated from, and a translation left in one propagates to languages nobody
 *    asked for.
 *
 * Usage:
 *   php tools/make-pot.php                 write the template
 *   php tools/make-pot.php --check         exit 1 if it is out of date, write nothing
 *   php tools/make-pot.php --domain=wsklad override the text domain
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------------------------
// Configuration, read from the plugin header so there is one place to change.
// ---------------------------------------------------------------------------------------------

$root = dirname(__DIR__);

$options = getopt('', ['check', 'list', 'domain::', 'exclude::']);

$header = (string) file_get_contents($root . '/wsklad.php');

$readHeader = function(string $key, string $fallback = '') use ($header) : string
{
	if(preg_match('~^[ \t\/*#@]*' . preg_quote($key, '~') . '\s*:\s*(.*)$~mi', $header, $m))
	{
		return trim($m[1]);
	}

	return $fallback;
};

// Named `$textDomain`, not `$domain`: `$domain` is a WordPress global, and reusing the name
// in a file that lints against the WordPress standard reads as overriding that global. The
// array key stays `'domain'` because that is the option name callers pass.
$textDomain = $options['domain'] ?? ($readHeader('Text Domain') ?: 'wsklad');

if('' === $textDomain)
{
	fwrite(STDERR, "Text Domain not found in the plugin header\n");
	exit(2);
}

$version = $readHeader('Version', '0.0.0');

$domainPath = trim($options['exclude'] ?? '');
$domainPath = '' !== $domainPath ? $domainPath : $readHeader('Domain Path');

$languagesDir = '' !== $domainPath
	? $root . '/' . trim($domainPath, '/')
	: $root . '/assets/languages';

/**
 * Directories that are never scanned. `vendor` holds a second copy of every string in some
 * projects; the rest are not plugin code.
 */
$skipSegments = ['vendor', 'node_modules', 'tests', 'tools', 'plans', 'docs', 'build', 'dist'];

// ---------------------------------------------------------------------------------------------
// Extraction
// ---------------------------------------------------------------------------------------------

/**
 * Is this offset inside a comment? A docblock mentioning `_n()` is a mention, not a call, and
 * reporting it as a skipped call would train the reader to ignore the skip list.
 */
function inside_comment(string $code, int $at) : bool
{
	$before = substr($code, 0, $at);

	// A block comment that opens and does not close before this point.
	$open  = strrpos($before, '/*');
	$close = strrpos($before, '*/');

	if(false !== $open && (false === $close || $open > $close))
	{
		return true;
	}

	// A line comment: no newline between the `//` and here.
	$slash = strrpos($before, '//');

	if(false === $slash)
	{
		return false;
	}

	// Not a `//` inside a string or a regex - crude, but a false positive only means a skipped
	// call is reported as a comment, which is the harmless direction.
	return false === strrpos(substr($before, 0, $slash), "\n");
}

/**
 * Gettext functions and which argument holds the string.
 *
 * ⚠ The indices are **zero-based**, matching what `read_arguments()` returns. An earlier
 * version used 1-based indices and every call resolved to its *second* argument - the text
 * domain - so 435 calls produced exactly one msgid, "wsklad". Order matters too: `esc_html__`
 * must be listed before `__`, otherwise the prefix is left behind.
 *
 * @var list<array{0: string, 1: int, 2: int, 3: int}> name, msgid argument, context argument,
 *                                                          plural argument (-1 when absent)
 */
$functions =
[
	// name            msgid  context  plural
	['esc_html_x',       0,       1,       -1],
	['esc_attr_x',       0,       1,       -1],
	['esc_html__',       0,      -1,       -1],
	['esc_attr__',       0,      -1,       -1],
	['esc_html_e',       0,      -1,       -1],
	['esc_attr_e',       0,      -1,       -1],
	['_ex',              0,       1,       -1],
	['_x',               0,       1,       -1],
	['_e',               0,      -1,       -1],
	['__',               0,      -1,       -1],
	['_nx',              0,      -1,        1],
	['_n',               0,      -1,        1],
];

$functionNames = array_map(static fn(array $f) => preg_quote($f[0], '~'), $functions);

$callPattern = '~\b(' . implode('|', $functionNames) . ')\s*\(~';

/**
 * @return array<string, string> relative path => file contents
 */
function collect_php_files(string $root, array $skipSegments) : array
{
	$files = [];

	$iterator = new RecursiveIteratorIterator(
		new RecursiveCallbackFilterIterator(
			new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
			static function (SplFileInfo $info) use ($skipSegments) : bool
			{
				$path = str_replace('\\', '/', $info->getPathname());

				if($info->isDir())
				{
					return !in_array($info->getFilename(), $skipSegments, true);
				}

				return 'php' === strtolower($info->getExtension());
			}
		)
	);

	foreach($iterator as $file)
	{
		$files[str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1))] = (string) file_get_contents($file->getPathname());
	}

	ksort($files);

	return $files;
}

/**
 * Read one PHP call's argument list, starting at the opening parenthesis.
 *
 * Returns the raw argument strings, or null when the call is not parseable - a variable-built
 * string, an unclosed call, a comment. Those are reported rather than skipped silently,
 * because a string that silently stops being translatable is the failure mode that costs the
 * most to notice.
 *
 * @return array<int,string>|null
 */
function read_arguments(string $code, int $open) : ?array
{
	// The opening parenthesis is consumed up front. Starting the loop *at* it put a leading
	// "(" into the first argument, which then failed every literal test downstream.
	$depth = 1;
	$args  = [];
	$buf   = '';
	$len   = strlen($code);

	for($i = $open + 1; $i < $len; $i++)
	{
		$char = $code[$i];

		if("\"" === $char || "'" === $char)
		{
			$quote = $char;
			$buf .= $char;
			$i++;

			for(; $i < $len; $i++)
			{
				$buf .= $code[$i];

				if('\\' === $code[$i] && $i + 1 < $len)
				{
					$i++;
					$buf .= $code[$i];
					continue;
				}

				if($code[$i] === $quote)
				{
					break;
				}
			}

			continue;
		}

		if('/' === $char && ($code[$i + 1] ?? '') === '/')
		{
			while($i < $len && "\n" !== $code[$i]) { $i++; }
			$buf .= ' ';
			continue;
		}

		if('#' === $char)
		{
			while($i < $len && "\n" !== $code[$i]) { $i++; }
			$buf .= ' ';
			continue;
		}

		if('/' === $char && ($code[$i + 1] ?? '') === '*')
		{
			$end = strpos($code, '*/', $i);
			$i   = false === $end ? $len : $end + 1;
			$buf .= ' ';
			continue;
		}

		if('(' === $char || '[' === $char)
		{
			$depth++;
			$buf .= $char;
			continue;
		}

		if(')' === $char || ']' === $char)
		{
			$depth--;

			if(0 === $depth && ')' === $char)
			{
				$args[] = trim($buf);
				return $args;
			}

			$buf .= $char;
			continue;
		}

		if(',' === $char && 1 === $depth)
		{
			$args[] = trim($buf);
			$buf    = '';
			continue;
		}

		$buf .= $char;
	}

	return null;
}

/**
 * Turn a PHP literal into its string value, or null when it is not a literal.
 */
function literal_value(string $raw) : ?string
{
	$raw = trim($raw);

	if('' === $raw)
	{
		return null;
	}

	if("'" === $raw[0])
	{
		if("'" !== substr($raw, -1))
		{
			return null;
		}

		return str_replace(["\\'", '\\\\'], ["'", '\\'], substr($raw, 1, -1));
	}

	if('"' === $raw[0])
	{
		if('"' !== substr($raw, -1))
		{
			return null;
		}

		$inner = substr($raw, 1, -1);

		// Double-quoted PHP: only the escapes that matter for a translatable string.
		return strtr($inner, ['\\n' => "\n", '\\t' => "\t", '\\"' => '"', '\\\\' => '\\', '\\$' => '$']);
	}

	return null;
}

/**
 * Join string literals joined by `.` or by `sprintf` of literals, which is how a long sentence
 * is often spread over several lines. A variable anywhere makes it non-literal, and therefore
 * untranslatable - correctly so, because gettext cannot reorder it at runtime.
 */
function resolve_expression(string $raw) : ?string
{
	$raw = trim($raw);

	if('' === $raw)
	{
		return null;
	}

	// A single literal, possibly wrapped in parentheses.
	if('(' === $raw[0] && ')' === substr($raw, -1) && 0 === substr_count($raw, '(') - 1)
	{
		return resolve_expression(substr($raw, 1, -1));
	}

	// Literal . literal . literal
	if(preg_match('~^(?:\s*(?:\'[^\']*\'|"(?:[^"\\\\]|\\\\.)*")\s*\.\s*)+$~', $raw))
	{
		$parts = preg_split('~(?:\s*\.\s*)~', $raw);
		$out   = '';

		foreach($parts as $part)
		{
			$value = literal_value($part);

			if(null === $value)
			{
				return null;
			}

			$out .= $value;
		}

		return $out;
	}

	return literal_value($raw);
}

/**
 * Pull the msgid and, for `_n`, the plural and the count, out of one call.
 *
 * @return array{msgid:string, msgid_plural:?string, count:?string}|null
 */
function read_call(string $code, int $at, array $function) : ?array
{
	$open = strpos($code, '(', $at);

	if(false === $open)
	{
		return null;
	}

	$args = read_arguments($code, $open);

	if(null === $args)
	{
		return null;
	}

	$msgidArg  = $function[1];
	$pluralArg = $function[3];

	$msgid = resolve_expression($args[$msgidArg] ?? '');

	if(null === $msgid || '' === $msgid)
	{
		return null;
	}

	$result = ['msgid' => $msgid, 'msgid_plural' => null, 'count' => null];

	if($pluralArg >= 0)
	{
		$plural = resolve_expression($args[$pluralArg] ?? '');

		if(null === $plural)
		{
			return null;
		}

		$result['msgid_plural'] = $plural;
		$result['count']        = literal_value($args[$pluralArg + 1] ?? '') ?? '1';
	}

	return $result;
}

/**
 * Is this call suppressed on purpose? The project's own marker plus the phpcs one.
 */
function is_suppressed(string $code, int $at) : bool
{
	$before = substr($code, max(0, $at - 220), min(220, $at));

	return false !== strpos($before, 'od-i18n: ignore')
		|| false !== strpos($before, 'phpcs:ignore')
		|| false !== strpos($before, 'phpcbf:ignore');
}

/**
 * Placeholders a translator must keep.
 *
 * The delimiter is `/` and not `~`: the character class contains `~` for printf's own escape,
 * and a `~` inside a class terminates a `~`-delimited pattern.
 *
 * @return list<string>
 */
function placeholders_of(string $string) : array
{
	preg_match_all('/%(?:\{[^}]*\})?\d*\$?[bcdeEfFgGosuxX~]|\{[a-zA-Z_][a-zA-Z0-9_]*\}/', $string, $m);

	return array_values(array_unique($m[0]));
}

$entries = [];
$skipped = [];

foreach(collect_php_files($root, $skipSegments) as $relative => $contents)
{
	if(!preg_match_all($callPattern, $contents, $calls, PREG_OFFSET_CAPTURE))
	{
		continue;
	}

	foreach($calls[0] as $index => $call)
	{
		$name = $calls[1][$index][0];
		$at   = (int) $calls[0][$index][1];

		/** @var array{0: string, 1: int, 2: int, 3: int}|null $function */
		$function = null;

		foreach($functions as $candidate)
		{
			if($candidate[0] === $name)
			{
				$function = $candidate;
				break;
			}
		}

		$GLOBALS['d_nofn']   = ($GLOBALS['d_nofn'] ?? 0) + (null === $function ? 1 : 0);
		$GLOBALS['d_sample'] = ($GLOBALS['d_sample'] ?? '') . '|' . $name;

		if(null === $function || is_suppressed($contents, $at))
		{
			continue;
		}

		$read = read_call($contents, $at, $function);

		if(null === $read)
		{
			$line = substr_count(substr($contents, 0, $at), "\n") + 1;

			if(inside_comment($contents, $at))
			{
				continue;
			}

			// A real call whose string cannot be extracted: a variable, or a construct this
			// parser does not model. Worth a line in the output - a string that silently stops
			// being translatable is the failure that costs the most to notice.
			$skipped[] = $relative . ':' . $line . '  ' . $name . '(...)';
			continue;
		}

		$line    = substr_count(substr($contents, 0, $at), "\n") + 1;
		$key     = $read['msgid'] . "\4" . (string) $read['msgid_plural'];

		if(!isset($entries[$key]))
		{
			$entries[$key] =
			[
				'msgid'        => $read['msgid'],
				'msgid_plural' => $read['msgid_plural'],
				'references'   => [],
				'comments'     => [],
			];
		}

		$entries[$key]['references'][] = $relative . ':' . $line;

		// A plural needs a translators: note, because the forms depend on the count.
		if(null !== $read['msgid_plural'] && !in_array('translators:', $entries[$key]['comments'], true))
		{
			$entries[$key]['comments'][] = 'translators:';
		}
	}
}

// ---------------------------------------------------------------------------------------------
// Rendering
// ---------------------------------------------------------------------------------------------

function po_escape(string $s) : string
{
	return str_replace(['\\', '"'], ['\\\\', '\\"'], $s);
}

/**
 * Render a quoted key. Long values are split at an exact offset so that concatenating the
 * parts reproduces the value - the failure this whole function exists to prevent.
 */
function render_key(string $keyword, string $value, int $chunk = 68) : string
{
	if(strlen($value) <= $chunk)
	{
		return $keyword . ' "' . po_escape($value) . '"' . "\n";
	}

	$out = $keyword . ' ""' . "\n";

	for($i = 0; $i < strlen($value); $i += $chunk)
	{
		$out .= '"' . po_escape(substr($value, $i, $chunk)) . '"' . "\n";
	}

	return $out;
}

/**
 * Self-check: read a rendered block back the way gettext reads it and confirm the value
 * survives. A generator that cannot prove this is a generator that will corrupt the catalogue
 * silently, which is what happened twice.
 *
 * @return list<string> problems
 */
function verify_roundtrip(string $rendered) : array
{
	$problems = [];
	$lines    = explode("\n", $rendered);
	$key      = null;
	$keyword  = null;
	$value    = '';

	$flush = static function() use (&$key, &$keyword, &$value, &$problems) : void
	{
		if(null === $keyword)
		{
			return;
		}

		if('' === $keyword)
		{
			$problems[] = 'empty ' . $key . ' - two keys in one block';
			return;
		}

		// Only the msgid side is required to survive. An empty msgstr is correct in a
		// template, and treating it as a failure flagged every single entry.
		if('' === $value && 0 === strpos($keyword, 'msgid'))
		{
			$problems[] = $keyword . ' round-tripped to an empty string';
		}

		$key     = null;
		$keyword = null;
		$value   = '';
	};

	foreach($lines as $line)
	{
		$line = trim($line);

		if('' === $line)
		{
			continue;
		}

		if(preg_match('~^(msgid|msgid_plural|msgstr(?:\[(\d+)\])?)\s*(.*)$~', $line, $m))
		{
			$flush();

			if(0 === strpos($m[3], '"'))
			{
				$keyword = $m[1];
				$value   = str_replace(['\\"', '\\\\'], ['"', '\\'], trim($m[3], '"'));
				continue;
			}

			return $problems; // `msgid` with no quotes: not something this generator writes
		}

		if(preg_match('~^"((?:[^"\\\\]|\\\\.)*)"$~', $line, $m))
		{
			if(null === $keyword)
			{
				$problems[] = 'continuation line with no key';
				continue;
			}

			$value .= str_replace(['\\"', '\\\\'], ['"', '\\'], $m[1]);
		}
	}

	$flush();

	return $problems;
}

$render = '';

foreach($entries as $entry)
{
	$block = '';

	foreach($entry['comments'] as $extractedComment)
	{
		$block .= '#. ' . $extractedComment . "\n";
	}

	foreach($entry['references'] as $reference)
	{
		$block .= '#: ' . $reference . "\n";
	}

	if(null !== $entry['msgid_plural'])
	{
		$block .= render_key('msgid', $entry['msgid']);
		$block .= render_key('msgid_plural', $entry['msgid_plural']);

		// A template declares no locale, so one empty form per language is enough here; the
		// plural forms are filled when a .po is generated from this file.
		$block .= 'msgstr[0] ""' . "\n";
	}
	else
	{
		$block .= render_key('msgid', $entry['msgid']);
		$block .= 'msgstr ""' . "\n";
	}

	foreach(verify_roundtrip($block) as $problem)
	{
		fwrite(STDERR, "round-trip failed: {$problem}\n");
		exit(1);
	}

	$render .= $block . "\n";
}

$potHeader = <<<POT
# Copyright (C) WSKLAD team
# This file is distributed under the GNU General Public License v3 or later.
msgid ""
msgstr ""
"Project-Id-Version: WSKLAD {$version}\n"
"Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/wsklad\n"
"POT-Creation-Date: " . gmdate('Y-m-d H:iO') . "\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
"Language-Team: LANGUAGE <LL@li.org>\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\n"
"X-Generator: wsklad tools/make-pot.php\n"
"X-Domain: {$textDomain}\n"

POT;

$output = $potHeader . "\n" . $render;

$target = $languagesDir . '/' . $textDomain . '.pot';

// ---------------------------------------------------------------------------------------------
// A template must not carry a translation. Assert it rather than trusting the renderer.
// ---------------------------------------------------------------------------------------------

if(preg_match('~[\x{0400}-\x{04FF}]~u', $output))
{
	fwrite(STDERR, "the generated template contains Cyrillic - a translation leaked into the .pot\n");
	exit(1);
}

if(preg_match('~^msgstr(?:\[\d\])?\s+"[^"]+"~m', $output))
{
	fwrite(STDERR, "the generated template contains a non-empty msgstr\n");
	exit(1);
}

if(!is_dir($languagesDir))
{
	fwrite(STDERR, "languages directory not found: {$languagesDir}\n");
	exit(2);
}

// `--list` prints one extractable string per line, as JSON, and stops.
//
// It exists so another tool can ask "what does the code actually contain" without writing a
// second gettext parser. A hand-written .pot reader is exactly the kind of thing that parses
// 292 of 356 strings and reports it as a clean run: msgid escapes, the plural form and the
// `msgctxt` keyword are all easy to miss and none of them announce themselves. Reusing this
// extractor means one implementation of "what is translatable", and the answer is the same one
// that produced the template.
if(isset($options['list']))
{
	$lines = [];

	foreach($entries as $entry)
	{
		$lines[] = json_encode(
			[
				'id' => $entry['msgid'],
				'plural' => $entry['msgid_plural'],
				'ctxt' => $entry['references'][0] ?? '',
			],
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);
	}

	echo implode("\n", $lines), "\n";

	exit(0);
}

if(isset($options['check']))
{
	$current = is_readable($target) ? (string) file_get_contents($target) : '';

	// The creation date moves on every run, so it is not part of the comparison.
	$normalise = static fn(string $t) : string => preg_replace('~^"POT-Creation-Date:.*$~m', '"POT-Creation-Date: X"', $t);

	printf("%d strings, %d problem(s)\n", count($entries), count($skipped));

	if($normalise($current) === $normalise($output))
	{
		printf("OK: %s is up to date (%d strings)\n", basename($target), count($entries));
		exit(0);
	}

	fwrite(STDERR, basename($target) . " is out of date; run: php tools/make-pot.php\n");
	exit(1);
}

file_put_contents($target, $output);

printf("wrote %s (%d strings, %d skipped)\n", $target, count($entries), count($skipped));

foreach($skipped as $note)
{
	printf("  skipped %s\n", $note);
}
