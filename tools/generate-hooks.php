<?php

/**
 * generate-hooks.php — generates docs/hooks.md from the source.
 *
 * Usage:
 *
 *     php tools/generate-hooks.php [--path=DIR] [--out=FILE] [--check] [--quiet]
 *
 * Exit codes:
 *
 *     0  docs/hooks.md was written, or is already up to date
 *     1  --check was given and docs/hooks.md is out of date
 *     2  the script could not run (bad arguments, unreadable source)
 *
 * A hand-maintained hook catalogue is a catalogue that is wrong within a week, and
 * this repository has ~99 hook names. So the file is generated, and `--check` is
 * the gate: CI runs it and fails when the committed file no longer matches the
 * code, which is the only arrangement that keeps a contract catalogue honest at
 * one day of development a week.
 *
 * `tools/validate-hooks.php` is the complementary gate. That one asks "is the hook
 * surface *sound*" — no duplicate names, nothing outside the `wsklad_` namespace.
 * This one asks "does the documentation match the surface". Both read the same
 * source, and this file deliberately does not modify that one.
 *
 * The scanning approach is the same one, for the same reason it is a token scan
 * rather than a regex: `do_action($name)` and a hook name inside a doc comment both
 * look like a hook to a regex, and a catalogue that lists hooks from comments is a
 * catalogue people learn to ignore.
 *
 * ## Determinism
 *
 * Running this twice must produce byte-identical output, or `--check` is useless
 * in CI. So: hooks are sorted by name, firing sites by file then line, and there
 * is no timestamp anywhere in the output. The header states the contract version
 * it was generated against, which is a fact about the code rather than about the
 * moment the file was written. A generation date would change on every run and
 * would make every commit that touched a docblock fail the gate for no reason.
 *
 * Standalone by design: no WordPress, no Composer, no dev dependencies. It has to
 * run in CI before anything is installed.
 *
 * @package Wsklad\Tools
 */

const WSKLAD_GEN_PREFIX = 'wsklad_';

/**
 * WordPress core hooks the plugin hooks into on purpose.
 *
 * @var string[]
 */
const WSKLAD_GEN_EXEMPTIONS = ['plugin_locale'];

/**
 * Directories never walked.
 *
 * @var string[]
 */
const WSKLAD_GEN_IGNORED =
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
 * @var array{path: string, out: string, check: bool, quiet: bool}
 */
$options = wsklad_gen_parse_options($argv);

$root = rtrim($options['path'], '/\\') . DIRECTORY_SEPARATOR;

if(!is_dir($root))
{
	fwrite(STDERR, "generate-hooks: not a directory: {$root}\n");

	exit(2);
}

$hooks = wsklad_gen_collect($root);

if(empty($hooks))
{
	fwrite(STDERR, "generate-hooks: no do_action()/apply_filters() calls found under {$root}\n");
	fwrite(STDERR, "generate-hooks: refusing to generate on an empty result — that means the scan is broken.\n");

	exit(2);
}

$markdown = wsklad_gen_render($hooks);

/**
 * The contract version is read from the class, not hard-coded, so this file and
 * the runtime constant cannot drift apart. The class is dependency-free by design.
 */
$contract = 'unknown';
$hooks_contract = $root . 'src' . DIRECTORY_SEPARATOR . 'Contract' . DIRECTORY_SEPARATOR . 'HooksContract.php';

if(is_readable($hooks_contract))
{
	$source = (string) @file_get_contents($hooks_contract);

	if(preg_match("/const\s+VERSION\s*=\s*'([^']+)'/", $source, $matches))
	{
		$contract = $matches[1];
	}
}

$markdown = str_replace('{{CONTRACT}}', $contract, $markdown);

if($options['check'])
{
	if(!is_file($options['out']))
	{
		fwrite(STDERR, "generate-hooks: {$options['out']} does not exist. Run: php tools/generate-hooks.php\n");

		exit(1);
	}

	// ⚠ Line-ending-insensitive, and deliberately so. The repository has
	// `core.autocrlf=true` and no .gitattributes, so a Windows checkout has CRLF here while
	// the generator emits LF. The content is identical; a byte comparison reports it as
	// stale regardless, which makes the gate permanently red on Windows and reproducible
	// nowhere. What this gate verifies is the hook list, not the checkout's line endings.
	$current = str_replace("\r\n", "\n", (string) @file_get_contents($options['out']));

	if($current === str_replace("\r\n", "\n", $markdown))
	{
		if(!$options['quiet'])
		{
			echo "OK: {$options['out']} is up to date (" . count($hooks) . " hook(s)).\n";
		}

		exit(0);
	}

	fwrite(STDERR, "generate-hooks: {$options['out']} is out of date.\n");
	fwrite(STDERR, "generate-hooks: run `php tools/generate-hooks.php` and commit the result.\n");

	exit(1);
}

$directory = dirname($options['out']);

if(!is_dir($directory) || !is_writable($directory))
{
	fwrite(STDERR, "generate-hooks: cannot write to {$directory}\n");

	exit(2);
}

if(false === @file_put_contents($options['out'], $markdown))
{
	fwrite(STDERR, "generate-hooks: failed to write {$options['out']}\n");

	exit(2);
}

if(!$options['quiet'])
{
	echo 'Wrote ' . $options['out'] . ' — ' . count($hooks) . ' hook(s), '
		. wsklad_gen_count_sites($hooks) . ' firing site(s), hook contract ' . $contract . ".\n";
}

exit(0);

/* =========================================================================
 * Implementation
 * ====================================================================== */

/**
 * @param array $argv
 *
 * @return array{path: string, out: string, check: bool, quiet: bool}
 */
function wsklad_gen_parse_options(array $argv): array
{
	$options =
	[
		'path'  => dirname(__DIR__),
		'out'   => '',
		'check' => false,
		'quiet' => false,
	];

	$out = '';

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

		if(0 === strpos($argument, '--path='))
		{
			$options['path'] = substr($argument, 7);
			continue;
		}

		if(0 === strpos($argument, '--out='))
		{
			$out = substr($argument, 6);
			continue;
		}

		if('--help' === $argument || '-h' === $argument)
		{
			echo "Usage: php tools/generate-hooks.php [--path=DIR] [--out=FILE] [--check] [--quiet]\n\n"
				. "  --path=DIR  project root (default: the directory containing tools/)\n"
				. "  --out=FILE  output file (default: <path>/docs/hooks.md)\n"
				. "  --check     exit 1 if the output file is out of date, write nothing\n"
				. "  --quiet     print nothing on success\n";

			exit(0);
		}

		fwrite(STDERR, "generate-hooks: unknown argument `{$argument}`. Try --help.\n");

		exit(2);
	}

	if('' === $out)
	{
		$out = rtrim($options['path'], '/\\') . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'hooks.md';
	}

	$options['out'] = $out;

	return $options;
}

/**
 * Every .php file under src/ and views/, keyed by project-relative path.
 *
 * @param string $root Project root with a trailing separator.
 *
 * @return array<string, string>
 */
function wsklad_gen_source_files(string $root): array
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

			if(wsklad_gen_is_ignored($relative))
			{
				continue;
			}

			$files[$relative] = $path;
		}
	}

	// Sorted so that two runs enumerate files in the same order on every platform,
	// including one where the filesystem returns them differently.
	ksort($files);

	return $files;
}

/**
 * @param string $relative
 *
 * @return bool
 */
function wsklad_gen_is_ignored(string $relative): bool
{
	foreach(explode('/', $relative) as $segment)
	{
		if(in_array($segment, WSKLAD_GEN_IGNORED, true))
		{
			return true;
		}
	}

	return false;
}

/**
 * @param string $root
 *
 * @return array<string, array>
 */
function wsklad_gen_collect(string $root): array
{
	$hooks = [];
	$deprecated = [];

	foreach(wsklad_gen_source_files($root) as $relative => $absolute)
	{
		$code = @file_get_contents($absolute);

		if(false === $code)
		{
			continue;
		}

		foreach(wsklad_gen_deprecated_names($code) as $name)
		{
			$deprecated[$name] = true;
		}

		// Per file, because the docblock sits above the call it documents.
		$since = wsklad_gen_since_tags($code);

		$tokens = @token_get_all($code);

		foreach(wsklad_gen_calls($tokens) as $call)
		{
			$name = $call['name'];

			if(!isset($hooks[$name]))
			{
				$hooks[$name] =
				[
					'sites' => [],
					'kinds' => [],
					'args'  => [],
					'core'  => false,
					'since' => isset($since[$name]) ? $since[$name] : '',
				];
			}

			$hooks[$name]['sites'][] = $relative . ':' . $call['line'];
			$hooks[$name]['kinds'][$call['kind']] = true;
			$hooks[$name]['args'][$call['args']] = true;

			if(!wsklad_gen_is_namespaced($name))
			{
				$hooks[$name]['core'] = true;
			}
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
 * @param string $name
 *
 * @return bool
 */
function wsklad_gen_is_namespaced(string $name): bool
{
	return 0 === strpos($name, WSKLAD_GEN_PREFIX) || in_array($name, WSKLAD_GEN_EXEMPTIONS, true);
}

/**
 * Hook names documented as deprecated, via `@deprecated <version> <hook_name>`.
 *
 * The deprecated name is the **first** identifier after the version. The same
 * annotation has to mention both the old and the new name for a human reader, and
 * a format that made the replacement the machine-readable one mislabelled the new
 * hook as deprecated.
 *
 * @param string $code
 *
 * @return string[]
 */
function wsklad_gen_deprecated_names(string $code): array
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

	$pattern = '/@deprecated\s+v?[0-9][^\s]*\s+(' . preg_quote(WSKLAD_GEN_PREFIX, '/') . '[a-z0-9_]+)/i';

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
 * Hook names with an `@since` tag, mapped to the version.
 *
 * Takes the **first** `@since` seen for a name, which is the version the hook was
 * introduced in. Later ones record an argument change, and the argument count
 * column already shows that.
 *
 * @param string $code
 *
 * @return array<string, string> Lower-cased hook name => version.
 */
function wsklad_gen_since_tags(string $code): array
{
	$found = [];

	if(false === stripos($code, '@since'))
	{
		return $found;
	}

	$lines = preg_split("/\r\n|\n|\r/", $code);

	if(!is_array($lines))
	{
		return $found;
	}

	$pattern = '/@since\s+v?([0-9][0-9A-Za-z.\-+]*)\s+(' . preg_quote(WSKLAD_GEN_PREFIX, '/') . '[a-z0-9_]+)/i';

	foreach($lines as $line)
	{
		if(false === stripos($line, '@since'))
		{
			continue;
		}

		if(preg_match($pattern, $line, $matches))
		{
			$name = strtolower($matches[2]);

			if(!isset($found[$name]))
			{
				$found[$name] = $matches[1];
			}
		}
	}

	return $found;
}

/**
 * Find literal `do_action('name', ...)` / `apply_filters('name', ...)` calls.
 *
 * @param array $tokens
 *
 * @return array<int, array{name: string, line: int, kind: string, args: int}>
 */
function wsklad_gen_calls(array $tokens): array
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
		$previous = wsklad_gen_previous_token($tokens, $i);

		if
		(
			is_array($previous)
			&& is_array($previous['token'])
			&& in_array
			(
				$previous['token'][0],
				[T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION],
				true
			)
		)
		{
			continue;
		}

		$open = wsklad_gen_next_token($tokens, $i);

		if(null === $open || '(' !== $open['text'])
		{
			continue;
		}

		$name = wsklad_gen_first_string_argument($tokens, $open['index']);

		if(null === $name || !preg_match('/^[a-z][a-z0-9_]*$/i', $name))
		{
			continue;
		}

		$calls[] =
		[
			'name' => $name,
			'line' => $token[2],
			'kind' => $function,
			'args' => wsklad_gen_argument_count($tokens, $open['index']),
		];
	}

	return $calls;
}

/**
 * How many arguments the call passes, excluding the hook name.
 *
 * This is the number a subscriber has to pass to `add_action()`, and it is part of
 * the frozen contract: changing it is a breaking change, which is why it is
 * published in docs/hooks.md rather than left in the source.
 *
 * @param array $tokens
 * @param int   $open Index of the `(` token.
 *
 * @return int
 */
function wsklad_gen_argument_count(array $tokens, int $open): int
{
	$total = count($tokens);
	$depth = 0;
	$count = 0;
	$seen = false;

	for($i = $open; $i < $total; $i++)
	{
		$token = $tokens[$i];
		$text = is_array($token) ? (string) $token[1] : (string) $token;

		// Anything not a plain string/array token cannot be a bracket.
		if(!is_array($token))
		{
			if('(' === $text || '[' === $text || '{' === $text)
			{
				$depth++;
				continue;
			}

			if(')' === $text || ']' === $text || '}' === $text)
			{
				$depth--;

				if($depth <= 0)
				{
					// A trailing comma before the closing paren is not an argument.
					return $seen ? $count : 0;
				}

				continue;
			}

			if(',' === $text && 1 === $depth)
			{
				$count++;
				continue;
			}
		}

		// Count the hook name, then discount it at the end.
		if(1 === $depth && !$seen && !in_array($text, [' ', "\t", "\n", "\r"], true))
		{
			$seen = true;
		}
	}

	return $seen ? $count - 1 : 0;
}

/**
 * @param array $tokens
 * @param int   $from
 *
 * @return array{index: int, token: array|string, text: string}|null
 */
function wsklad_gen_previous_token(array $tokens, int $from)
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
function wsklad_gen_next_token(array $tokens, int $from)
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
function wsklad_gen_first_string_argument(array $tokens, int $open)
{
	$argument = wsklad_gen_next_token($tokens, $open);

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

	return wsklad_gen_unquote($literal);
}

/**
 * @param string $literal
 *
 * @return string
 */
function wsklad_gen_unquote(string $literal): string
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
 *
 * @return int
 */
function wsklad_gen_count_sites(array $hooks): int
{
	$count = 0;

	foreach($hooks as $data)
	{
		$count += count($data['sites']);
	}

	return $count;
}

/**
 * Render the Markdown catalogue.
 *
 * @param array $hooks
 *
 * @return string
 */
function wsklad_gen_render(array $hooks): string
{
	$deprecations = 0;
	$max_args = 0;

	foreach($hooks as $data)
	{
		if(!empty($data['deprecated']))
		{
			$deprecations++;
		}

		foreach(array_keys($data['args']) as $args)
		{
			$max_args = max($max_args, (int) $args);
		}
	}

	$out = "# WSKLAD hook reference\n\n";
	$out .= "<!--\n";
	$out .= "  GENERATED FILE — DO NOT EDIT BY HAND.\n";
	$out .= "  Source: src/ and views/, scanned for do_action() and apply_filters().\n";
	$out .= "  Regenerate: php tools/generate-hooks.php\n";
	$out .= "  Gate in CI:  php tools/generate-hooks.php --check\n";
	$out .= "\n";
	$out .= "  There is no generation date on purpose. A date would change on every run and\n";
	$out .= "  would fail --check for commits that changed nothing.\n";
	$out .= "-->\n\n";

	$out .= "**Hook contract: `{{CONTRACT}}`.**\n\n";

	$out .= sprintf
	(
		"%d hook name(s), %d firing site(s), %d deprecated.\n\n",
		count($hooks),
		wsklad_gen_count_sites($hooks),
		$deprecations
	);

	$out .= "The hook contract is versioned separately from the plugin. A hook rename, a removal,\n";
	$out .= "or a change in the number of arguments is a breaking change and bumps the contract\n";
	$out .= "version, not the plugin version. An extension declares the contract range it supports\n";
	$out .= "in `requires.hooks`, and WSKLAD checks it before loading the extension. See\n";
	$out .= "[EXTENDING.md](../EXTENDING.md).\n\n";

	$out .= "## Subscribing\n\n";
	$out .= "Actions and filters are ordinary WordPress hooks. Pass the accepted argument count\n";
	$out .= "explicitly — it is part of the contract, not a detail:\n\n";
	$out .= "```php\n";
	$out .= "add_action('wsklad_example_hook', function(array \$payload) {\n";
	$out .= "    // ...\n";
	$out .= "}, 10, 1);\n";
	$out .= "```\n\n";

	$out .= "For a filter, return the value:\n\n";
	$out .= "```php\n";
	$out .= "add_filter('wsklad_example_filter', function(\$items) {\n";
	$out .= "    return array_merge(\$items, [ 'extra' ]);\n";
	$out .= "}, 10, 1);\n";
	$out .= "```\n\n";

	$out .= "## Deprecation policy\n\n";
	$out .= "A hook that is being removed is annotated in the source as:\n\n";
	$out .= "```\n";
	$out .= "@deprecated <version> <hook_name>\n";
	$out .= "```\n\n";
	$out .= "The old name keeps firing as an alias for at least one full major of the contract.\n";
	$out .= "Hooks marked *deprecated* below are in that window: they still work, and they will\n";
	$out .= "not stop working without a major release.\n\n";

	$out .= "## All hooks\n\n";
	$out .= "| Hook | Kind | Args | Since | Status | Fired at |\n";
	$out .= "|---|---|---|---|---|---|\n";

	foreach($hooks as $name => $data)
	{
		$kinds = [];

		foreach(array_keys($data['kinds']) as $kind)
		{
			$kinds[] = 'do_action' === $kind ? 'action' : 'filter';
		}

		sort($kinds);

		$args = array_keys($data['args']);
		sort($args, SORT_NUMERIC);
		$args = implode(', ', $args);

		$sites = $data['sites'];
		sort($sites);

		$status = 'stable';

		if(!empty($data['deprecated']))
		{
			$status = '**deprecated** — alias kept until 2.0.0';
		}
		elseif(!empty($data['core']))
		{
			$status = 'WordPress core hook';
		}

		if(count($data['sites']) > 1 && empty($data['deprecated']))
		{
			$status .= ', fired from ' . count($data['sites']) . ' places';
		}

		$out .= sprintf
		(
			"| `%s` | %s | %s | %s | %s | %s |\n",
			$name,
			implode(' / ', $kinds),
			'' === $args ? '0' : $args,
			isset($data['since']) && '' !== $data['since'] ? $data['since'] : '—',
			$status,
			implode('<br>', array_map(function ($site) {
				return '`' . $site . '`';
			}, $sites))
		);
	}

	$out .= "\n";

	/**
	 * A second, alphabetical index. With ~100 rows the table is long enough that
	 * finding one hook means scrolling, and a contract nobody can search is a
	 * contract nobody reads.
	 */
	$out .= "## Index by name\n\n";

	$names = array_keys($hooks);
	sort($names);

	foreach($names as $name)
	{
		$data = $hooks[$name];

		$out .= sprintf
		(
			"- `%s` — %s, %d arg(s)%s\n",
			$name,
			count($data['kinds']) > 1 ? 'action + filter' : (array_key_first($data['kinds']) === 'do_action' ? 'action' : 'filter'),
			max(array_map('intval', array_keys($data['args']))),
			empty($data['deprecated']) ? '' : ' — **deprecated**, alias kept until 2.0.0'
		);
	}

	return $out;
}
