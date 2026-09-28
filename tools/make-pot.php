<?php

/**
 * make-pot.php — the translation template generator.
 *
 * Usage:
 *
 *     php tools/make-pot.php [--path=DIR] [--out=FILE] [--domain=wsklad]
 *                            [--check] [--quiet]
 *
 * Exit codes:
 *
 *     0  the template was written (or, with --check, is up to date)
 *     1  the template is stale, under --check
 *     2  the script could not run (bad arguments, unreadable source)
 *
 * WHY THIS EXISTS INSTEAD OF `wp i18n make-pot`
 * ================================================
 *
 * The WordPress.org i18n toolchain, `msgfmt` and `msgmerge` are not installed on
 * this machine and there is no network to fetch them. Rather than leave the
 * template three years stale — which is what it was — this reads the source with
 * PHP's own tokenizer and writes the `.pot` directly.
 *
 * It walks the token stream rather than running a regular expression over the
 * text, because a regex cannot tell `__('Add accounts', 'wsklad')` from the same
 * text inside a doc comment or a disabled `//` line. A template that claims a
 * string nobody can translate is worse than a missing one, because it looks
 * complete.
 *
 * What is deliberately not here: merging. This writes the template and nothing
 * else. It never touches a `.po`. Fuzzy-match state, translator comments and
 * existing translations are the translator's, and a hand-merge loses them
 * silently. `msgmerge` is the right tool and the maintainer should run it.
 *
 * DETERMINISM
 * ============
 *
 * Two runs over unchanged sources produce byte-identical output. Entries are
 * sorted, references within an entry are sorted, and `POT-Creation-Date` is
 * derived from the newest source file rather than from the wall clock — so the
 * template does not churn on every run, and a diff in it always means a string
 * actually changed. Set `SOURCE_DATE_EPOCH` to pin the date explicitly for a
 * reproducible build.
 *
 * @package Wsklad\Tools
 */

declare(ticks = 1);

/**
 * gettext call signatures: argument index => what the argument means.
 *
 * The index sets are exactly the ones the existing template's keyword list
 * declares, including the `_noop` family. `_nop`/`_noopt` variants only
 * register a string for later; the translator still has to supply both forms,
 * so they belong in the template.
 *
 * The indexes are zero-based and they are not interchangeable: `_n()` takes a
 * count before the domain, so its domain is argument 3 and not argument 2.
 * Reading the count as the domain does not fail loudly — it produces a template
 * with the plural entry simply missing, which is the kind of thing that gets
 * noticed a year later, in a language nobody on the team reads.
 *
 * @var array<string, array<string, int>>
 */
const WSKLAD_POT_FUNCTIONS =
[
	'__'              => ['msgid' => 0, 'domain' => 1],
	'_e'              => ['msgid' => 0, 'domain' => 1],
	'esc_attr__'      => ['msgid' => 0, 'domain' => 1],
	'esc_attr_e'      => ['msgid' => 0, 'domain' => 1],
	'esc_html__'      => ['msgid' => 0, 'domain' => 1],
	'esc_html_e'      => ['msgid' => 0, 'domain' => 1],
	'_x'              => ['msgid' => 0, 'context' => 1, 'domain' => 2],
	'_ex'             => ['msgid' => 0, 'context' => 1, 'domain' => 2],
	'esc_attr_x'      => ['msgid' => 0, 'context' => 1, 'domain' => 2],
	'esc_html_x'      => ['msgid' => 0, 'context' => 1, 'domain' => 2],
	'_n'              => ['msgid' => 0, 'plural' => 1, 'domain' => 3],
	'_nx'             => ['msgid' => 0, 'plural' => 1, 'context' => 3, 'domain' => 4],
	'_n_noop'         => ['msgid' => 0, 'plural' => 1, 'domain' => 2],
	'_nx_noop'        => ['msgid' => 0, 'plural' => 1, 'context' => 2, 'domain' => 3],
	'__ngettext_noop' => ['msgid' => 0, 'plural' => 1, 'domain' => 2],
];

/**
 * Directories never walked.
 *
 * @var string[]
 */
const WSKLAD_POT_IGNORED =
[
	'vendor',
	'node_modules',
	'.git',
	'.idea',
	'.phpunit.cache',
	'plans',
	'dist',
	'build',
];

/**
 * Longest output line, quotes included. gettext's own default.
 *
 * @var int
 */
const WSKLAD_POT_WIDTH = 79;

$wsklad_pot_options = wsklad_pot_parse_options($argv);

$wsklad_pot_root = rtrim($wsklad_pot_options['path'], '/\\') . DIRECTORY_SEPARATOR;

if(!is_dir($wsklad_pot_root))
{
	fwrite(STDERR, "make-pot: not a directory: {$wsklad_pot_root}\n");

	exit(2);
}

$wsklad_pot_entries = [];
$wsklad_pot_skipped = [];
$wsklad_pot_newest  = 0;

foreach(['src', 'views'] as $wsklad_pot_directory)
{
	foreach(wsklad_pot_source_files($wsklad_pot_root, $wsklad_pot_directory) as $wsklad_pot_relative => $wsklad_pot_path)
	{
		$wsklad_pot_newest = max($wsklad_pot_newest, (int) @filemtime($wsklad_pot_path));

		wsklad_pot_scan
		(
			$wsklad_pot_path,
			$wsklad_pot_relative,
			$wsklad_pot_entries,
			$wsklad_pot_skipped,
			$wsklad_pot_options['domain']
		);
	}
}

if(empty($wsklad_pot_entries))
{
	fwrite(STDERR, "make-pot: no translatable strings found under " . $wsklad_pot_root . "\n");
	fwrite(STDERR, "make-pot: refusing to write an empty template — that means the scan is broken.\n");

	$wsklad_pot_reasons = array_keys($wsklad_pot_skipped);
	sort($wsklad_pot_reasons, SORT_STRING);

	foreach($wsklad_pot_reasons as $wsklad_pot_reason)
	{
		fwrite(STDERR, sprintf("  every call was skipped: %s\n", $wsklad_pot_reason));
	}

	exit(2);
}

ksort($wsklad_pot_entries, SORT_STRING);

$wsklad_pot_body = wsklad_pot_render
(
	$wsklad_pot_entries,
	$wsklad_pot_newest,
	$wsklad_pot_options['domain'],
	$wsklad_pot_root
);

// A .pot file ends with exactly one newline. The block separator emitted above leaves a
// blank line after the final entry, and `git diff --check` reports that as
// "new blank line at EOF" on every regeneration. Trim here — before --check, not after —
// so the body the check compares is the same one the writer emits.
$wsklad_pot_body = rtrim($wsklad_pot_body, "\r\n") . "\n";

if($wsklad_pot_options['check'])
{
	$wsklad_pot_current = is_file($wsklad_pot_options['out'])
		? (string) @file_get_contents($wsklad_pot_options['out'])
		: '';

	if($wsklad_pot_current === $wsklad_pot_body)
	{
		if(!$wsklad_pot_options['quiet'])
		{
			echo sprintf("%s is up to date: %d entry/entries.\n", $wsklad_pot_options['out'], count($wsklad_pot_entries));
		}

		exit(0);
	}

	fwrite(STDERR, sprintf("%s is stale: it would change.\n", $wsklad_pot_options['out']));

	exit(1);
}

$wsklad_pot_directory = dirname($wsklad_pot_options['out']);

if(!is_dir($wsklad_pot_directory))
{
	fwrite(STDERR, "make-pot: no such directory: {$wsklad_pot_directory}\n");

	exit(2);
}

if(false === @file_put_contents($wsklad_pot_options['out'], $wsklad_pot_body))
{
	fwrite(STDERR, "make-pot: could not write {$wsklad_pot_options['out']}\n");

	exit(2);
}

if(!$wsklad_pot_options['quiet'])
{
	$wsklad_pot_plurals = 0;

	foreach($wsklad_pot_entries as $wsklad_pot_entry)
	{
		if(!empty($wsklad_pot_entry['plural']))
		{
			$wsklad_pot_plurals++;
		}
	}

	echo sprintf("Wrote %s\n", $wsklad_pot_options['out']);
	echo sprintf("%d entry/entries (%d plural), %d call site(s), skipped %d (other domain or dynamic).\n",
		count($wsklad_pot_entries),
		$wsklad_pot_plurals,
		array_sum(array_map(function ($entry) {
			return count($entry['references']);
		}, $wsklad_pot_entries)),
		count($wsklad_pot_skipped)
	);

	if(!empty($wsklad_pot_skipped) && !$wsklad_pot_options['quiet'])
	{
		$wsklad_pot_by_reason = [];

		foreach(array_keys($wsklad_pot_skipped) as $wsklad_pot_reason)
		{
			if(!isset($wsklad_pot_by_reason[$wsklad_pot_reason]))
			{
				$wsklad_pot_by_reason[$wsklad_pot_reason] = 0;
			}

			$wsklad_pot_by_reason[$wsklad_pot_reason]++;
		}

		ksort($wsklad_pot_by_reason);

		foreach($wsklad_pot_by_reason as $wsklad_pot_reason => $wsklad_pot_count)
		{
			echo sprintf("  skipped %d: %s\n", $wsklad_pot_count, $wsklad_pot_reason);
		}
	}

	echo "The .po files were not touched. Run msgmerge over them to sync.\n";
}

exit(0);

/**
 * @param array $argv
 *
 * @return array{path: string, out: string, domain: string, check: bool, quiet: bool}
 */
function wsklad_pot_parse_options(array $argv): array
{
	$options =
	[
		'path'   => dirname(__DIR__),
		'out'    => '',
		'domain' => 'wsklad',
		'check'  => false,
		'quiet'  => false,
	];

	foreach(array_slice($argv, 1) as $argument)
	{
		if('--check' === $argument)
		{
			$options['check'] = true;

			continue;
		}

		if('--quiet' === $argument || '-q' === $argument)
		{
			$options['quiet'] = true;

			continue;
		}

		if('--out=' === substr($argument, 0, 6))
		{
			$value = substr($argument, 6);

			if('' === $value)
			{
				wsklad_pot_usage();

				exit(2);
			}

			$options['out'] = $value;

			continue;
		}

		if('--domain=' === substr($argument, 0, 9))
		{
			$value = substr($argument, 9);

			if('' === $value)
			{
				wsklad_pot_usage();

				exit(2);
			}

			$options['domain'] = $value;

			continue;
		}

		if('--path=' === substr($argument, 0, 7))
		{
			$value = substr($argument, 7);

			if('' !== $value)
			{
				$options['path'] = $value;
			}

			continue;
		}

		wsklad_pot_usage();
		fwrite(STDERR, "make-pot: unknown argument `{$argument}`.\n");

		exit(2);
	}

	$options['path'] = rtrim($options['path'], '/\\') . DIRECTORY_SEPARATOR;

	if('' === $options['out'])
	{
		$options['out'] = $options['path'] . 'assets/languages/' . $options['domain'] . '.pot';
	}

	return $options;
}

function wsklad_pot_usage()
{
	fwrite(STDERR, "Usage: php tools/make-pot.php [--path=DIR] [--out=FILE] [--domain=wsklad] [--check] [--quiet]\n");
}

/**
 * @param string $root
 * @param string $directory
 *
 * @return array<string, string>
 */
function wsklad_pot_source_files(string $root, string $directory): array
{
	$files    = [];
	$absolute = $root . $directory;

	if(!is_dir($absolute))
	{
		return $files;
	}

	$iterator = new RecursiveIteratorIterator
	(
		new RecursiveDirectoryIterator($absolute, FilesystemIterator::SKIP_DOTS)
	);

	/** @var SplFileInfo $file */
	foreach($iterator as $file)
	{
		if(!$file->isFile() || 'php' !== strtolower($file->getExtension()))
		{
			continue;
		}

		$path     = str_replace('\\', '/', $file->getPathname());
		$relative = substr($path, strlen(rtrim($root, '/\\')) + 1);

		if(wsklad_pot_is_ignored($relative))
		{
			continue;
		}

		$files[$relative] = $path;
	}

	ksort($files);

	return $files;
}

/**
 * @param string $relative
 *
 * @return bool
 */
function wsklad_pot_is_ignored(string $relative): bool
{
	foreach(explode('/', $relative) as $segment)
	{
		if(in_array($segment, WSKLAD_POT_IGNORED, true))
		{
			return true;
		}
	}

	return false;
}

/**
 * Collect every translatable literal in one file.
 *
 * @param string $path
 * @param string $relative
 * @param array  $entries   Modified in place.
 * @param array  $skipped   Modified in place: reason => true.
 * @param string $domain
 */
function wsklad_pot_scan(string $path, string $relative, array &$entries, array &$skipped, string $domain)
{
	$code = @file_get_contents($path);

	if(false === $code)
	{
		$skipped['unreadable file'] = true;

		return;
	}

	$tokens = @token_get_all($code);

	if(!is_array($tokens))
	{
		$skipped['could not tokenize'] = true;

		return;
	}

	$count = count($tokens);

	for($index = 0; $index < $count; $index++)
	{
		$token = $tokens[$index];

		if(!is_array($token) || T_STRING !== $token[0] || !isset(WSKLAD_POT_FUNCTIONS[$token[1]]))
		{
			continue;
		}

		$previous = wsklad_pot_previous_significant($tokens, $index - 1);

		if(null !== $previous && is_array($tokens[$previous])
			&& in_array($tokens[$previous][0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION], true))
		{
			$skipped[$token[1] . '() called as a method, not the gettext function'] = true;

			continue;
		}

		$open = wsklad_pot_next_significant($tokens, $index + 1);

		if(null === $open || '(' !== wsklad_pot_token_text($tokens[$open]))
		{
			continue;
		}

		$close = wsklad_pot_matching_bracket($tokens, $open);

		if(null === $close)
		{
			continue;
		}

		$signature = WSKLAD_POT_FUNCTIONS[$token[1]];
		$arguments = wsklad_pot_arguments($tokens, $open, $close);

		$msgid = wsklad_pot_string_argument($arguments, $signature['msgid']);

		if(null === $msgid || '' === $msgid)
		{
			$skipped[$token[1] . '() msgid is not a literal'] = true;

			continue;
		}

		$plural = array_key_exists('plural', $signature)
			? wsklad_pot_string_argument($arguments, $signature['plural'])
			: null;

		$context = array_key_exists('context', $signature)
			? wsklad_pot_string_argument($arguments, $signature['context'])
			: null;

		$found_domain = array_key_exists('domain', $signature)
			? wsklad_pot_string_argument($arguments, $signature['domain'])
			: null;

		if(null !== $found_domain && $found_domain !== $domain)
		{
			$skipped['text domain `' . $found_domain . '`'] = true;

			continue;
		}

		if(null === $found_domain)
		{
			$skipped[$token[1] . '() text domain is not a literal'] = true;

			continue;
		}

		$key = (string) $context . "\x04" . $msgid . "\x04" . (string) $plural;

		if(!isset($entries[$key]))
		{
			$entries[$key] =
			[
				'msgid'      => $msgid,
				'plural'     => $plural,
				'context'    => $context,
				'references' => [],
			];
		}

		$reference = $relative . ':' . $token[2];

		if(!in_array($reference, $entries[$key]['references'], true))
		{
			$entries[$key]['references'][] = $reference;
		}
	}
}

/**
 * Split a call's arguments, as raw token slices.
 *
 * @param array $tokens
 * @param int   $open  Index of the `(`.
 * @param int   $close Index of the matching `)`.
 *
 * @return array<int, array>
 */
function wsklad_pot_arguments(array $tokens, int $open, int $close): array
{
	$arguments = [];
	$depth     = 0;
	$current   = [];

	// Inside the parentheses only. The outer pair is not part of any argument, and
	// counting it as depth 1 would make every comma look like it was nested.
	for($index = $open + 1; $index < $close; $index++)
	{
		$token = $tokens[$index];

		if(is_string($token))
		{
			if('(' === $token || '[' === $token)
			{
				$depth++;
			}
			elseif(')' === $token || ']' === $token)
			{
				$depth--;
			}
			elseif(',' === $token && 0 === $depth)
			{
				$arguments[] = $current;
				$current     = [];

				continue;
			}
		}

		$current[] = $token;
	}

	if(!empty($current))
	{
		$arguments[] = $current;
	}

	return $arguments;
}

/**
 * The value of one argument, if it is a single string literal.
 *
 * Concatenation is deliberately not followed: `'Save ' . $what` has no msgid a
 * translator can be given, and guessing one would put a wrong string in front of
 * them.
 *
 * @param array $arguments
 * @param int   $position
 *
 * @return string|null
 */
function wsklad_pot_string_argument(array $arguments, int $position)
{
	if(!isset($arguments[$position]))
	{
		return null;
	}

	$significant = [];

	foreach($arguments[$position] as $token)
	{
		if(is_array($token) && !wsklad_pot_is_significant($token))
		{
			continue;
		}

		$significant[] = $token;
	}

	if(1 !== count($significant) || !is_array($significant[0]) || T_CONSTANT_ENCAPSED_STRING !== $significant[0][0])
	{
		return null;
	}

	return wsklad_pot_unquote($significant[0][1]);
}

/**
 * @param string $literal
 *
 * @return string
 */
function wsklad_pot_unquote(string $literal): string
{
	$quote = substr($literal, 0, 1);
	$body  = substr($literal, 1, -1);

	if("'" === $quote)
	{
		return str_replace(["\\'", '\\\\'], ["'", '\\'], $body);
	}

	return stripcslashes($body);
}

/**
 * @param array $entries
 * @param int   $newest
 * @param string $domain
 * @param string $root
 *
 * @return string
 */
function wsklad_pot_render(array $entries, int $newest, string $domain, string $root): string
{
	$out = wsklad_pot_header($newest, $domain, $root);

	foreach($entries as $entry)
	{
		$references = $entry['references'];
		sort($references, SORT_STRING);

		foreach(wsklad_pot_wrap('#: ', $references) as $line)
		{
			$out .= $line . "\n";
		}

		if(null !== $entry['context'] && '' !== $entry['context'])
		{
			$out .= wsklad_pot_keyword('msgctxt', $entry['context']) . "\n";
		}

		$out .= wsklad_pot_keyword('msgid', $entry['msgid']) . "\n";

		if(null !== $entry['plural'] && '' !== $entry['plural'])
		{
			$out .= wsklad_pot_keyword('msgid_plural', $entry['plural']) . "\n";
			$out .= "msgstr[0] \"\"\n";
			$out .= "msgstr[1] \"\"\n";
		}
		else
		{
			$out .= "msgstr \"\"\n";
		}

		$out .= "\n";
	}

	return $out;
}

/**
 * @param int    $newest
 * @param string $domain
 * @param string $root
 *
 * @return string
 */
function wsklad_pot_header(int $newest, string $domain, string $root): string
{
	$version = wsklad_pot_plugin_version($root);
	$epoch   = getenv('SOURCE_DATE_EPOCH');

	if(false === $epoch || '' === trim((string) $epoch))
	{
		$epoch = $newest;
	}

	$epoch = (int) $epoch;
	$date  = gmdate('Y-m-d H:iO', $epoch > 0 ? $epoch : 0);

	$header =
	[
		'Project-Id-Version'         => 'WSKLAD ' . $version,
		'Report-Msgid-Bugs-To'       => 'https://wordpress.org/support/plugin/' . $domain,
		'POT-Creation-Date'          => $date,
		'PO-Revision-Date'           => 'YEAR-MO-DA HO:MI+ZONE',
		'Last-Translator'            => 'FULL NAME <EMAIL@ADDRESS>',
		'Language-Team'              => 'LANGUAGE <LL@li.org>',
		'MIME-Version'               => '1.0',
		'Content-Type'               => 'text/plain; charset=UTF-8',
		'Content-Transfer-Encoding'  => '8bit',
		'Plural-Forms'               => 'nplurals=INTEGER; plural=EXPRESSION;',
		'X-Generator'                => 'wsklad tools/make-pot.php',
		'X-Domain'                   => $domain,
	];

	$out = "msgid \"\"\n";
	$out .= "msgstr \"\"\n";

	foreach($header as $name => $value)
	{
		$line = '"' . $name . ': ' . wsklad_pot_escape($value) . '\n"';

		if(strlen($line) <= WSKLAD_POT_WIDTH)
		{
			$out .= $line . "\n";

			continue;
		}

		$out .= '"' . $name . ': ' . "\n";

		foreach(wsklad_pot_escape_lines(wsklad_pot_escape($value)) as $chunk)
		{
			$out .= $chunk . "\n";
		}

		$out .= "\"\\n\"\n";
	}

	return $out . "\n";
}

/**
 * The `Version:` from the plugin header, so the template names the release.
 *
 * @param string $root
 *
 * @return string
 */
function wsklad_pot_plugin_version(string $root): string
{
	$path = $root . 'wsklad.php';

	if(!is_file($path))
	{
		return 'unknown';
	}

	$code = (string) @file_get_contents($path);

	if(preg_match('~^\s*\*\s*Version:\s*(.+?)\s*$~m', $code, $match))
	{
		return trim($match[1]);
	}

	return 'unknown';
}

/**
 * `msgid "…"` and friends, wrapped to the column limit.
 *
 * @param string $keyword
 * @param string $value
 *
 * @return string
 */
function wsklad_pot_keyword(string $keyword, string $value): string
{
	$escaped = wsklad_pot_escape($value);
	$single  = $keyword . ' "' . $escaped . '"';

	if(strlen($single) <= WSKLAD_POT_WIDTH)
	{
		return $single;
	}

	$out  = $keyword . " \"\"\n";
	$out .= implode("\n", wsklad_pot_escape_lines($escaped));

	return $out;
}

/**
 * Split an already-escaped string into quoted lines no wider than the limit.
 *
 * The break goes *after* a space, and the space stays on the earlier line, which
 * is what gettext does — a translator diffing two templates expects that shape.
 *
 * The split is done by scanning for spaces rather than by `explode(' ', …)` and
 * rejoining. That looks equivalent and is not: a run of two spaces explodes into
 * an empty word, and rejoining turns it into three. The strings in this plugin
 * contain deliberate double spaces, and a template that silently changes a
 * msgid is a template translators cannot match their existing work against.
 *
 * @param string $escaped
 *
 * @return string[]
 */
function wsklad_pot_escape_lines(string $escaped): array
{
	$lines   = [];
	$current = '';
	$length  = strlen($escaped);
	$cursor  = 0;

	while($cursor < $length)
	{
		$space = strpos($escaped, ' ', $cursor);

		if(false === $space)
		{
			$current .= substr($escaped, $cursor);
			$lines[]  = '"' . $current . '"';

			break;
		}

		$chunk     = substr($escaped, $cursor, $space - $cursor + 1);
		$candidate = $current . $chunk;

		if('' !== $current && strlen('"' . $candidate . '"') > WSKLAD_POT_WIDTH)
		{
			$lines[] = '"' . $current . '"';
			$current = $chunk;
		}
		else
		{
			$current = $candidate;
		}

		$cursor = $space + 1;
	}

	if(empty($lines))
	{
		$lines[] = '""';
	}

	return $lines;
}

/**
 * A `#:` reference line set, wrapped to the column limit.
 *
 * @param string   $prefix
 * @param string[] $items
 *
 * @return string[]
 */
function wsklad_pot_wrap(string $prefix, array $items): array
{
	$lines  = [];
	$buffer = $prefix;
	$last   = count($items) - 1;

	foreach($items as $index => $item)
	{
		$candidate = $index === 0 ? $buffer . $item : $buffer . ' ' . $item;

		if(strlen($candidate) > WSKLAD_POT_WIDTH && '' !== trim($buffer))
		{
			$lines[] = rtrim($buffer);
			$buffer  = $prefix . $item;

			continue;
		}

		$buffer = $candidate;
	}

	$lines[] = rtrim($buffer);

	return $lines;
}

/**
 * @param string $value
 *
 * @return string
 */
function wsklad_pot_escape(string $value): string
{
	$value = str_replace('\\', '\\\\', $value);
	$value = str_replace('"', '\\"', $value);
	$value = str_replace("\t", '\\t', $value);
	$value = str_replace("\r", '\\r', $value);

	return str_replace("\n", '\\n', $value);
}

/**
 * @param array $tokens
 * @param int   $from
 *
 * @return int|null
 */
function wsklad_pot_next_significant(array $tokens, int $from)
{
	$count = count($tokens);

	for($index = $from; $index < $count; $index++)
	{
		if(wsklad_pot_is_significant($tokens[$index]))
		{
			return $index;
		}
	}

	return null;
}

/**
 * @param array $tokens
 * @param int   $from
 *
 * @return int|null
 */
function wsklad_pot_previous_significant(array $tokens, int $from)
{
	for($index = $from; $index >= 0; $index--)
	{
		if(wsklad_pot_is_significant($tokens[$index]))
		{
			return $index;
		}
	}

	return null;
}

/**
 * @param array|string $token
 *
 * @return bool
 */
function wsklad_pot_is_significant($token): bool
{
	if(is_string($token))
	{
		return true;
	}

	return !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
}

/**
 * @param array|string $token
 *
 * @return string
 */
function wsklad_pot_token_text($token): string
{
	return is_array($token) ? (string) $token[1] : (string) $token;
}

/**
 * The index of the `)` closing the `(` at $open.
 *
 * @param array $tokens
 * @param int   $open
 *
 * @return int|null
 */
function wsklad_pot_matching_bracket(array $tokens, int $open)
{
	$depth = 0;
	$count = count($tokens);

	for($index = $open; $index < $count; $index++)
	{
		if(!wsklad_pot_is_significant($tokens[$index]))
		{
			continue;
		}

		$text = wsklad_pot_token_text($tokens[$index]);

		if('(' === $text || '[' === $text)
		{
			$depth++;

			continue;
		}

		if(')' === $text || ']' === $text)
		{
			$depth--;

			if(0 === $depth)
			{
				return $index;
			}
		}
	}

	return null;
}
