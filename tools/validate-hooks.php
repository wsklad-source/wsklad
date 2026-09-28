<?php

/**
 * validate-hooks.php — the hook contract gate.
 *
 * Usage:
 *
 *     php tools/validate-hooks.php [--path=DIR] [--quiet] [--max=N]
 *
 * Exit codes:
 *
 *     0  the hook surface is consistent
 *     1  a duplicate name, or a name outside the `wsklad_` namespace
 *     2  the script could not run (bad arguments, unreadable source)
 *
 * It scans `src/` and `views/` for literal `do_action()` / `apply_filters()`
 * names and fails when:
 *
 *   - one name is fired from more than one place. Every subscriber to that name
 *     then runs more than once, silently.
 *   - a name is not `wsklad_`-prefixed. An unprefixed action is fired in the
 *     global namespace, where every plugin on the site can hear it.
 *
 * `plugin_locale` is the one exemption: it is a WordPress core filter that the
 * plugin subscribes to on purpose, and it is not the plugin's to namespace.
 *
 * This file is deliberately self-contained. It duplicates the scanning logic in
 * `tests/stubs/scanner.php` rather than requiring it, because it has to run from
 * a release tarball, from a `git bisect` in the middle of a broken refactor, and
 * from a `composer hooks:validate` on a checkout where the test dependencies were
 * never installed. A gate that cannot run is not a gate.
 *
 * @package Wsklad\Tools
 */

declare(ticks = 1);

const WSKLAD_HOOK_PREFIX = 'wsklad_';

/**
 * Core WordPress hooks the plugin legitimately hooks into.
 *
 * @var string[]
 */
const WSKLAD_HOOK_EXEMPTIONS =
[
	'plugin_locale',
];

/**
 * Directories never walked.
 *
 * @var string[]
 */
const WSKLAD_HOOK_IGNORED =
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
 * Options parsed from argv().
 *
 * @var array{path: string, quiet: bool, max: int}
 */
$wsklad_options = wsklad_parse_options($argv);

$wsklad_root = rtrim($wsklad_options['path'], '/\\') . DIRECTORY_SEPARATOR;

if(!is_dir($wsklad_root))
{
	fwrite(STDERR, "validate-hooks: not a directory: {$wsklad_root}\n");

	exit(2);
}

$wsklad_hooks = wsklad_collect_hooks($wsklad_root);

if(empty($wsklad_hooks))
{
	fwrite(STDERR, "validate-hooks: no do_action()/apply_filters() calls found under " . $wsklad_root . "\n");
	fwrite(STDERR, "validate-hooks: refusing to pass on an empty result — that means the scan is broken.\n");

	exit(2);
}

wsklad_print_table($wsklad_hooks, $wsklad_options);

$wsklad_problems = wsklad_find_problems($wsklad_hooks);

if(empty($wsklad_problems))
{
	if(!$wsklad_options['quiet'])
	{
		echo "\nOK: " . count($wsklad_hooks) . ' distinct hook name(s), no duplicates, all namespaced.\n';
	}

	exit(0);
}

echo "\n";

foreach($wsklad_problems as $wsklad_problem)
{
	echo $wsklad_problem . "\n\n";
}

echo 'FAILED: ' . count($wsklad_problems) . " hook contract violation(s).\n";

exit(1);

/* =========================================================================
 * Implementation
 * ====================================================================== */

/**
 * @param array $argv
 *
 * @return array{path: string, quiet: bool, max: int}
 */
function wsklad_parse_options(array $argv): array
{
	$options =
	[
		'path'  => dirname(__DIR__),
		'quiet' => false,
		'max'   => 0,
	];

	foreach(array_slice($argv, 1) as $argument)
	{
		if('--quiet' === $argument || '-q' === $argument)
		{
			$options['quiet'] = true;
			continue;
		}

		if(0 === strpos($argument, '--path='))
		{
			$options['path'] = substr($argument, 7);
			continue;
		}

		if(0 === strpos($argument, '--max='))
		{
			$options['max'] = max(0, (int) substr($argument, 6));
			continue;
		}

		if('--help' === $argument || '-h' === $argument)
		{
			echo "Usage: php tools/validate-hooks.php [--path=DIR] [--quiet] [--max=N]\n\n"
				. "  --path=DIR   project root (default: the directory containing tools/)\n"
				. "  --quiet      print nothing on success\n"
				. "  --max=N      show at most N table rows; 0 means all\n";

			exit(0);
		}

		fwrite(STDERR, "validate-hooks: unknown argument `{$argument}`. Try --help.\n");

		exit(2);
	}

	return $options;
}

/**
 * Walk the source and collect every literal hook name.
 *
 * @param string $root Project root with a trailing separator.
 *
 * @return array<string, array{occurrences: array<int, string>, kinds: array<int, string>}>
 */
function wsklad_collect_hooks(string $root): array
{
	$hooks = [];
	$deprecated = [];

	foreach(wsklad_source_files($root) as $relative => $absolute)
	{
		$code = @file_get_contents($absolute);

		if(false === $code)
		{
			continue;
		}

		foreach(wsklad_deprecated_names($code) as $name)
		{
			$deprecated[$name] = true;
		}

		$tokens = @token_get_all($code);

		foreach(wsklad_hook_calls($tokens) as $call)
		{
			$name = $call['name'];

			if(!isset($hooks[$name]))
			{
				$hooks[$name] = ['occurrences' => [], 'kinds' => []];
			}

			$hooks[$name]['occurrences'][] = $relative . ':' . $call['line'];
			$hooks[$name]['kinds'][$call['kind']] = true;
		}
	}

	foreach(array_keys($deprecated) as $name)
	{
		if(isset($hooks[$name]))
		{
			$hooks[$name]['deprecated'] = true;
		}
	}

	ksort($hooks);

	return $hooks;
}

/**
 * Whether the call on a given line sits under an `@deprecated` note.
 *
 * A deprecated alias is *supposed* to be fired from a different place than its
 * replacement, or to coexist with it under the same name. Exempting those is what
 * makes the duplicate check meaningful: without it, every future deprecation would
 * either fail the gate or tempt someone into deleting the alias, which is exactly the
 * back-compatibility break the alias exists to prevent.
 *
 * The test is at the level of the hook *name*, not the firing site: any `@deprecated`
 * annotation anywhere in the codebase that names the hook marks the name as deprecated.
 * A site-level heuristic was tried first and was wrong — the annotation sits above the
 * first call in a template, so a second call further down was reported as a duplicate
 * even though both belong to the same deprecation.
 *
 * The machine-readable form is `@deprecated <version> <hook_name>`, with the deprecated
 * name as the **first** identifier after the version. Naming the *replacement* instead
 * was tried and mislabelled the new hook as deprecated, because the same annotation has
 * to mention both names for a human reader.
 *
 * @param string $code
 *
 * @return string[] Hook names documented as deprecated
 */
function wsklad_deprecated_names(string $code): array
{
	$names = [];

	if(false === stripos($code, '@deprecated'))
	{
		return $names;
	}

	$lines = preg_split("/\r\n|\n|\r/", $code);

	if(!is_array($lines))
	{
		return $names;
	}

	$pattern = '/@deprecated\s+v?[0-9][^\s]*\s+(' . preg_quote(WSKLAD_HOOK_PREFIX, '/') . '[a-z0-9_]+)/i';

	foreach($lines as $line)
	{
		if(false === stripos($line, '@deprecated'))
		{
			continue;
		}

		if(preg_match($pattern, $line, $matches))
		{
			$names[strtolower($matches[1])] = true;
		}
	}

	return array_keys($names);
}

/**
 * Every .php file under src/ and views/, keyed by project-relative path.
 *
 * @param string $root
 *
 * @return array<string, string>
 */
function wsklad_source_files(string $root): array
{
	$files = [];

	foreach(['src', 'views'] as $directory)
	{
		$absolute = $root . $directory;

		if(!is_dir($absolute))
		{
			continue;
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

			if(wsklad_is_ignored($relative))
			{
				continue;
			}

			$files[$relative] = $path;
		}
	}

	ksort($files);

	return $files;
}

/**
 * @param string $relative
 *
 * @return bool
 */
function wsklad_is_ignored(string $relative): bool
{
	foreach(explode('/', $relative) as $segment)
	{
		if(in_array($segment, WSKLAD_HOOK_IGNORED, true))
		{
			return true;
		}
	}

	return false;
}

/**
 * Find literal `do_action('name')` / `apply_filters('name')` calls in a token list.
 *
 * Working on tokens rather than on the raw text is what keeps `do_action($name)`
 * and a hook name inside a doc comment out of the results. A regex cannot tell
 * those apart, and a gate that reports a hook from a comment is a gate people
 * learn to ignore.
 *
 * @param array $tokens
 *
 * @return array<int, array{name: string, line: int, kind: string}>
 */
function wsklad_hook_calls(array $tokens): array
{
	$calls = [];
	$total = count($tokens);

	for($i = 0; $i < $total; $i++)
	{
		$token = $tokens[$i];

		if(!is_array($token) || T_STRING !== $token[0])
		{
			continue;
		}

		$function = strtolower($token[1]);

		if('do_action' !== $function && 'apply_filters' !== $function)
		{
			continue;
		}

		// A method or static call is not the hook API.
		$previous = wsklad_previous_token($tokens, $i);

		if
		(
			is_array($previous)
			&& is_array($previous['token'])
			&& in_array
			(
				$previous['token'][0],
				[T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NULLSAFE_OBJECT_OPERATOR, T_FUNCTION],
				true
			)
		)
		{
			continue;
		}

		$open = wsklad_next_token($tokens, $i);

		if(null === $open || '(' !== $open['text'])
		{
			continue;
		}

		$name = wsklad_first_string_argument($tokens, $open['index']);

		if(null === $name || !preg_match('/^[a-z][a-z0-9_]*$/i', $name))
		{
			continue;
		}

		$calls[] =
		[
			'name' => $name,
			'line' => $token[2],
			'kind' => $function,
		];
	}

	return $calls;
}

/**
 * @param array $tokens
 * @param int   $from
 *
 * @return array{index: int, token: array|string, text: string}|null
 */
function wsklad_previous_token(array $tokens, int $from)
{
	for($i = $from - 1; $i >= 0; $i--)
	{
		if(is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true))
		{
			continue;
		}

		return
		[
			'index' => $i,
			'token' => $tokens[$i],
			'text'  => is_array($tokens[$i]) ? (string) $tokens[$i][1] : (string) $tokens[$i],
		];
	}

	return null;
}

/**
 * @param array $tokens
 * @param int   $from
 *
 * @return array{index: int, token: array|string, text: string}|null
 */
function wsklad_next_token(array $tokens, int $from)
{
	$total = count($tokens);

	for($i = $from + 1; $i < $total; $i++)
	{
		if(is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true))
		{
			continue;
		}

		return
		[
			'index' => $i,
			'token' => $tokens[$i],
			'text'  => is_array($tokens[$i]) ? (string) $tokens[$i][1] : (string) $tokens[$i],
		];
	}

	return null;
}

/**
 * @param array $tokens
 * @param int   $open
 *
 * @return string|null
 */
function wsklad_first_string_argument(array $tokens, int $open)
{
	$argument = wsklad_next_token($tokens, $open);

	if(null === $argument || !is_array($argument['token']) || T_CONSTANT_ENCAPSED_STRING !== $argument['token'][0])
	{
		return null;
	}

	$literal = $argument['token'][1];

	// `"wsklad_{$x}"` is not a constant.
	if('"' === $literal[0] && preg_match('/(?<!\\\\)\$|(?<!\\\\)\{\$/', $literal))
	{
		return null;
	}

	return wsklad_unquote($literal);
}

/**
 * @param string $literal
 *
 * @return string
 */
function wsklad_unquote(string $literal): string
{
	if(strlen($literal) < 2)
	{
		return '';
	}

	$quote = $literal[0];

	return str_replace
	(
		['\\' === $quote ? "\\\\'" : "\\\\\"", '\\\\'],
		["'", '\\'],
		substr($literal, 1, -1)
	);
}

/**
 * @param array $hooks
 * @param array $options
 *
 * @return void
 */
function wsklad_print_table(array $hooks, array $options)
{
	if($options['quiet'])
	{
		return;
	}

	$rows = [];

	foreach($hooks as $name => $data)
	{
		if(!empty($data['deprecated']))
		{
			$status = 'deprecated';
		}
		elseif(count($data['occurrences']) > 1)
		{
			$status = 'DUPLICATE';
		}
		else
		{
			$status = 'ok';
		}
		$rows[] =
		[
			$status,
			$name,
			implode('/', array_keys($data['kinds'])),
			(string) count($data['occurrences']),
			implode('  ', $data['occurrences']),
		];
	}

	$headers = ['STATUS', 'HOOK', 'KIND', 'N', 'FIRED AT'];
	$widths  = [];

	foreach($headers as $index => $header)
	{
		$widths[$index] = strlen($header);
	}

	foreach($rows as $row)
	{
		foreach($row as $index => $cell)
		{
			$widths[$index] = max($widths[$index], strlen($cell));
		}
	}

	$shown = 0;

	echo "\nWSKLAD hook surface\n";
	echo str_repeat('-', 100) . "\n";

	foreach($rows as $row)
	{
		if($options['max'] > 0 && $shown >= $options['max'])
		{
			echo sprintf('… and %d more hook(s). Use --max=0 to list everything.', count($rows) - $shown) . "\n";
			break;
		}

		$line = '';

		foreach($row as $index => $cell)
		{
			$line .= str_pad($cell, $widths[$index] + 2);
		}

		echo rtrim($line) . "\n";
		$shown++;
	}

	echo str_repeat('-', 100) . "\n";
	echo sprintf
	(
		"%d distinct hook name(s), %d firing site(s), %d duplicate name(s), %d outside the `%s` namespace.\n",
		count($hooks),
		array_sum(array_map(function ($data) {
			return count($data['occurrences']);
		}, $hooks)),
		wsklad_count_problems($hooks, 'duplicate'),
		wsklad_count_problems($hooks, 'namespace'),
		WSKLAD_HOOK_PREFIX
	);
}

/**
 * @param array  $hooks
 * @param string $kind
 *
 * @return int
 */
function wsklad_count_problems(array $hooks, string $kind): int
{
	$count = 0;

	foreach(wsklad_find_problems($hooks, $kind) as $ignored)
	{
		$count++;
	}

	return $count;
}

/**
 * @param array       $hooks
 * @param string|null $only Restrict to one problem class.
 *
 * @return string[]
 */
function wsklad_find_problems(array $hooks, string $only = null): array
{
	$problems = [];

	foreach($hooks as $name => $data)
	{
		if(count($data['occurrences']) > 1 && empty($data['deprecated']))
		{			$problems[] = sprintf
			(
				"DUPLICATE  %s\n"
				. "           fired %d time(s):\n"
				. "             %s\n"
				. "           Every subscriber to this name runs more than once, with nothing to indicate it.",
				$name,
				count($data['occurrences']),
				implode("\n             ", $data['occurrences'])
			);
		}

		if(0 === strpos($name, WSKLAD_HOOK_PREFIX) || in_array($name, WSKLAD_HOOK_EXEMPTIONS, true))
		{
			continue;
		}

		$problems[] = sprintf
		(
			"NAMESPACE  %s\n"
			. "           not `%s`-prefixed and not an exempt core hook:\n"
			. "             %s",
			$name,
			WSKLAD_HOOK_PREFIX,
			implode("\n             ", $data['occurrences'])
		);
	}

	if(is_null($only))
	{
		return $problems;
	}

	$filtered = [];

	foreach($problems as $problem)
	{
		if(0 === strpos($problem, 'DUPLICATE') && 'duplicate' === $only)
		{
			$filtered[] = $problem;
		}

		if(0 === strpos($problem, 'NAMESPACE') && 'namespace' === $only)
		{
			$filtered[] = $problem;
		}
	}

	return $filtered;
}
