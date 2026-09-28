<?php

/**
 * validate-settings.php — the dead-settings gate.
 *
 * Usage:
 *
 *     php tools/validate-settings.php [--path=DIR] [--quiet] [--only-dead]
 *
 * Exit codes:
 *
 *     0  every declared setting key is read somewhere, or is declared inert on purpose
 *     1  at least one declared setting key is read by nothing
 *     2  the script could not run (bad arguments, unreadable source)
 *
 * It scans `src/Admin/Settings/` for the keys the admin forms declare, scans
 * `src/Settings/` for the keys the option groups still carry but no longer
 * render, scans `src/` for keys read through `->get( '…' )` on a settings
 * object, and fails when a declared key is read by nothing.
 *
 * A checkbox that saves a value nothing reads is a lie told to the shop owner:
 * they change it, nothing happens, and the next time they need it they do not
 * trust their own settings screen. That is a worse outcome than the setting not
 * being offered, which is why this is a gate and not a note in the backlog.
 *
 * Two kinds of key are deliberately *not* failures:
 *
 *   - `type => 'title'` fields. Those are section headings. They are never read
 *     and are never meant to be; `Form::save()` skips them.
 *   - keys listed in a settings class's `LEGACY_DEFAULTS`. Those are fields that
 *     were removed from the UI once nothing read them, and are kept in the stored
 *     option so an upgrading install does not lose the value. They are reported
 *     as `preserved` so the list stays visible instead of quietly growing.
 *
 * This file is deliberately self-contained, for the same reason
 * `tools/validate-hooks.php` is: it has to run from a release tarball and from a
 * `composer settings:validate` on a checkout with no test dependencies. A gate
 * that cannot run is not a gate.
 *
 * @package Wsklad\Tools
 */

declare(ticks = 1);

/**
 * Field type that is a heading, not a value.
 *
 * @var string
 */
const WSKLAD_SETTINGS_TITLE_TYPE = 'title';

/**
 * Constant on a settings class that lists the keys it keeps but no longer renders.
 *
 * @var string
 */
const WSKLAD_SETTINGS_LEGACY_CONST = 'LEGACY_DEFAULTS';

/**
 * Directories never walked.
 *
 * @var string[]
 */
const WSKLAD_SETTINGS_IGNORED =
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
 * @var array{path: string, quiet: bool, only_dead: bool}
 */
$wsklad_options = wsklad_settings_parse_options($argv);

$wsklad_root = rtrim($wsklad_options['path'], '/\\') . DIRECTORY_SEPARATOR;

if(!is_dir($wsklad_root))
{
	fwrite(STDERR, "validate-settings: not a directory: {$wsklad_root}\n");

	exit(2);
}

$wsklad_declared = wsklad_settings_collect_declared($wsklad_root);
$wsklad_preserved = wsklad_settings_collect_preserved($wsklad_root);
$wsklad_reads     = wsklad_settings_collect_reads($wsklad_root);

if(empty($wsklad_declared) && empty($wsklad_reads))
{
	fwrite(STDERR, "validate-settings: no form fields and no settings reads found under " . $wsklad_root . "\n");
	fwrite(STDERR, "validate-settings: refusing to pass on an empty result — that means the scan is broken.\n");
	fwrite(STDERR, sprintf
	(
		"validate-settings: scanned %d form file(s) and %d source file(s); %d declared, %d preserved, %d read.\n",
		count(wsklad_settings_php_files($wsklad_root, 'src/Admin/Settings')),
		count(wsklad_settings_php_files($wsklad_root, 'src')),
		count($wsklad_declared),
		count($wsklad_preserved),
		count($wsklad_reads)
	));

	exit(2);
}

/**
 * Every key the plugin reads, whether or not a form declares it.
 *
 * `extensions_tools` was read for years and declared by no form, so a user had no
 * way to set it. That is the mirror image of a dead key, and it is worth seeing,
 * so it is reported too — but on its own it is not a failure.
 *
 * @var string[]
 */
$wsklad_undeclared = array_diff(array_keys($wsklad_reads), array_keys($wsklad_declared), array_keys($wsklad_preserved));

/**
 * Keys a form offers that nothing reads. The failures.
 *
 * @var array<string, array>
 */
$wsklad_dead = [];

foreach($wsklad_declared as $key => $data)
{
	if(WSKLAD_SETTINGS_TITLE_TYPE === $data['type'])
	{
		continue;
	}

	if(isset($wsklad_reads[$key]))
	{
		continue;
	}

	$wsklad_dead[$key] = $data;
}

ksort($wsklad_dead);
ksort($wsklad_undeclared);

wsklad_settings_print_table($wsklad_declared, $wsklad_preserved, $wsklad_reads, $wsklad_dead, $wsklad_undeclared, $wsklad_options);

if(!empty($wsklad_dead))
{
	fwrite(STDERR, "\n");

	foreach($wsklad_dead as $key => $data)
	{
		fwrite(STDERR, "DEAD  {$key}\n");
		fwrite(STDERR, "      declared as `{$data['type']}` at {$data['origin']}\n");
		fwrite(STDERR, "      read by nothing in src/ — a user can change it and nothing will happen.\n");
		fwrite(STDERR, "      Remove the form field, then list the key in the option group's\n");
		fwrite(STDERR, "      LEGACY_DEFAULTS so the stored value stays readable.\n\n");
	}

	fwrite(STDERR, sprintf("%d declared setting key(s) are read by nothing.\n", count($wsklad_dead)));

	exit(1);
}

exit(0);

/**
 * @param array $argv
 *
 * @return array{path: string, quiet: bool, only_dead: bool}
 */
function wsklad_settings_parse_options(array $argv): array
{
	$options =
	[
		'path'      => dirname(__DIR__),
		'quiet'     => false,
		'only_dead' => false,
	];

	foreach(array_slice($argv, 1) as $argument)
	{
		if('--quiet' === $argument || '-q' === $argument)
		{
			$options['quiet'] = true;

			continue;
		}

		if('--only-dead' === $argument)
		{
			$options['only_dead'] = true;

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

		fwrite(STDERR, "validate-settings: unknown argument `{$argument}`.\n");
		fwrite(STDERR, "Usage: php tools/validate-settings.php [--path=DIR] [--quiet] [--only-dead]\n");

		exit(2);
	}

	return $options;
}

/**
 * Every `.php` file under a project-relative directory, keyed by relative path.
 *
 * @param string $root
 * @param string $directory
 *
 * @return array<string, string>
 */
function wsklad_settings_php_files(string $root, string $directory): array
{
	$files     = [];
	$absolute  = $root . $directory;

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

		if(wsklad_settings_is_ignored($relative))
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
function wsklad_settings_is_ignored(string $relative): bool
{
	foreach(explode('/', $relative) as $segment)
	{
		if(in_array($segment, WSKLAD_SETTINGS_IGNORED, true))
		{
			return true;
		}
	}

	return false;
}

/**
 * Keys the admin forms declare, and the type of each.
 *
 * Reads `$fields['key'] = [ 'type' => '…' … ];` assignments. Working on tokens
 * rather than on the raw text is what keeps a key mentioned in a comment, or one
 * inside a nested array, out of the results.
 *
 * @param string $root
 *
 * @return array<string, array{type: string, origin: string}>
 */
function wsklad_settings_collect_declared(string $root): array
{
	$declared = [];

	foreach(wsklad_settings_php_files($root, 'src/Admin/Settings') as $relative => $path)
	{
		$tokens = wsklad_settings_tokens($path);

		if(empty($tokens))
		{
			continue;
		}

		$count = count($tokens);

		for($index = 0; $index < $count; $index++)
		{
			$token = $tokens[$index];

			if(!is_array($token) || T_VARIABLE !== $token[0] || '$fields' !== $token[1])
			{
				continue;
			}

			$open = wsklad_settings_next_significant($tokens, $index + 1);

			if(null === $open || '[' !== wsklad_settings_token_text($tokens[$open]))
			{
				continue;
			}

			$key_token = wsklad_settings_next_significant($tokens, $open + 1);

			if(null === $key_token || T_CONSTANT_ENCAPSED_STRING !== $tokens[$key_token][0])
			{
				continue;
			}

			$close = wsklad_settings_next_significant($tokens, $key_token + 1);

			if(null === $close || ']' !== wsklad_settings_token_text($tokens[$close]))
			{
				continue;
			}

			$assign = wsklad_settings_next_significant($tokens, $close + 1);

			if(null === $assign || '=' !== wsklad_settings_token_text($tokens[$assign]))
			{
				continue;
			}

			$bracket = wsklad_settings_next_significant($tokens, $assign + 1);

			if(null === $bracket || '[' !== wsklad_settings_token_text($tokens[$bracket]))
			{
				continue;
			}

			$end = wsklad_settings_matching_bracket($tokens, $bracket);

			if(null === $end)
			{
				continue;
			}

			$body = wsklad_settings_slice_text($path, $tokens, $bracket, $end);

			if(!preg_match("~'type'\s*=>\s*'([a-z_]+)'~", $body, $type_match))
			{
				continue;
			}

			$key = wsklad_settings_unquote($tokens[$key_token][1]);

			if('' === $key || isset($declared[$key]))
			{
				continue;
			}

			$declared[$key] =
			[
				'type'   => $type_match[1],
				'origin' => "{$relative}:" . $tokens[$key_token][2],
			];
		}
	}

	ksort($declared);

	return $declared;
}

/**
 * Keys an option group carries but no longer renders.
 *
 * @param string $root
 *
 * @return array<string, string>
 */
function wsklad_settings_collect_preserved(string $root): array
{
	$preserved = [];

	foreach(wsklad_settings_php_files($root, 'src/Settings') as $relative => $path)
	{
		$tokens = wsklad_settings_tokens($path);

		if(empty($tokens))
		{
			continue;
		}

		$count = count($tokens);

		for($index = 0; $index < $count; $index++)
		{
			$token = $tokens[$index];

			if(!is_array($token) || T_CONST !== $token[0])
			{
				continue;
			}

			$name = wsklad_settings_next_significant($tokens, $index + 1);

			if(null === $name || !is_array($tokens[$name]) || T_STRING !== $tokens[$name][0]
				|| WSKLAD_SETTINGS_LEGACY_CONST !== $tokens[$name][1])
			{
				continue;
			}

			$assign = wsklad_settings_next_significant($tokens, $name + 1);

			if(null === $assign || '=' !== wsklad_settings_token_text($tokens[$assign]))
			{
				continue;
			}

			$bracket = wsklad_settings_next_significant($tokens, $assign + 1);

			if(null === $bracket || '[' !== wsklad_settings_token_text($tokens[$bracket]))
			{
				continue;
			}

			$end = wsklad_settings_matching_bracket($tokens, $bracket);

			if(null === $end)
			{
				continue;
			}

			$body = wsklad_settings_slice_text($path, $tokens, $bracket, $end);

			if(!preg_match_all("~'([^']+)'\s*=>~", $body, $matches))
			{
				continue;
			}

			foreach($matches[1] as $key)
			{
				if(!isset($preserved[$key]))
				{
					$preserved[$key] = "{$relative}:" . $tokens[$name][2];
				}
			}
		}
	}

	ksort($preserved);

	return $preserved;
}

/**
 * Keys read through a settings object, mapped to the places that read them.
 *
 * The receiver has to mention `settings`, or the class is read as a settings
 * read: `$account->get('name')` is an account, not a key. Working on tokens is
 * also what keeps a `get('…')` inside a comment out of the results.
 *
 * @param string $root
 *
 * @return array<string, string[]>
 */
function wsklad_settings_collect_reads(string $root): array
{
	$reads = [];

	foreach(wsklad_settings_php_files($root, 'src') as $relative => $path)
	{
		$tokens = wsklad_settings_tokens($path);

		if(empty($tokens))
		{
			continue;
		}

		$count = count($tokens);

		for($index = 0; $index < $count; $index++)
		{
			$token = $tokens[$index];

			if(!is_array($token) || T_STRING !== $token[0] || 'get' !== $token[1])
			{
				continue;
			}

			$operator = wsklad_settings_previous_significant($tokens, $index - 1);

			if(null === $operator || T_OBJECT_OPERATOR !== $tokens[$operator][0])
			{
				continue;
			}

			if(!wsklad_settings_receiver_is_settings($tokens, $operator))
			{
				continue;
			}

			$open = wsklad_settings_next_significant($tokens, $index + 1);

			if(null === $open || '(' !== wsklad_settings_token_text($tokens[$open]))
			{
				continue;
			}

			$key_token = wsklad_settings_next_significant($tokens, $open + 1);

			if(null === $key_token || T_CONSTANT_ENCAPSED_STRING !== $tokens[$key_token][0])
			{
				continue;
			}

			$key = wsklad_settings_unquote($tokens[$key_token][1]);

			if('' === $key)
			{
				continue;
			}

			$reads[$key][] = "{$relative}:" . $tokens[$key_token][2];
		}
	}

	ksort($reads);

	return $reads;
}

/**
 * Whether the expression to the left of `->get(` is a settings object.
 *
 * Walks backwards with a bracket depth counter. A flat "stop at the first bracket"
 * rule looks correct and is not: in `wsklad()->settings()->get(…)` the first
 * bracket met going backwards is the `)` that *closes* `settings()`, so the walk
 * would stop before it ever saw the word. `()` therefore raises and lowers the
 * depth instead of ending the expression, and only `;`, `,`, `=`, `{`, `}` and
 * friends end it at depth zero.
 *
 * @param array $tokens
 * @param int   $operator Index of the T_OBJECT_OPERATOR.
 *
 * @return bool
 */
function wsklad_settings_receiver_is_settings(array $tokens, int $operator): bool
{
	$depth = 0;
	$parts = [];

	for($index = $operator - 1; $index >= 0 && count($parts) < 80; $index--)
	{
		$token = $tokens[$index];

		if(!wsklad_settings_is_significant($token))
		{
			continue;
		}

		$text = wsklad_settings_token_text($token);

		if(')' === $text || ']' === $text)
		{
			$depth++;

			continue;
		}

		if('(' === $text || '[' === $text)
		{
			if(0 === $depth)
			{
				break;
			}

			$depth--;

			continue;
		}

		if(0 === $depth)
		{
			if(';' === $text || ',' === $text || '=' === $text || '{' === $text
				|| '}' === $text || ':' === $text)
			{
				break;
			}
		}

		array_unshift($parts, $text);
	}

	return (bool) preg_match('~settings~i', implode(' ', $parts));
}

/**
 * @param string $path
 *
 * @return array
 */
function wsklad_settings_tokens(string $path): array
{
	$code = @file_get_contents($path);

	if(false === $code)
	{
		return [];
	}

	$tokens = @token_get_all($code);

	return is_array($tokens) ? $tokens : [];
}

/**
 * The source text between two tokens, inclusive.
 *
 * A string token — `[`, `]`, `(`, `)` — has no line or offset of its own, so the
 * offset is derived by walking the list rather than read off the token.
 *
 * @param string $path
 * @param array  $tokens
 * @param int    $from
 * @param int    $to
 *
 * @return string
 */
function wsklad_settings_slice_text(string $path, array $tokens, int $from, int $to): string
{
	$code = (string) @file_get_contents($path);
	$from = wsklad_settings_offset($tokens, $from);

	return substr($code, $from, wsklad_settings_offset($tokens, $to) + strlen(wsklad_settings_token_text($tokens[$to])) - $from);
}

/**
 * The byte offset of a token within the source it was read from.
 *
 * @param array $tokens
 * @param int   $index
 *
 * @return int
 */
function wsklad_settings_offset(array $tokens, int $index): int
{
	$offset = 0;

	for($position = 0; $position < $index; $position++)
	{
		$offset += strlen(wsklad_settings_token_text($tokens[$position]));
	}

	return $offset;
}

/**
 * The text of a token, whether `token_get_all()` returned a string or an array.
 *
 * A string token is a single character — `[`, `]`, `(`, `)`, `;` — and reading
 * `$token[1]` on one of those is an uninitialized offset, not the second
 * character. Every comparison in this file goes through here so that cannot
 * happen by accident.
 *
 * @param array|string $token
 *
 * @return string
 */
function wsklad_settings_token_text($token): string
{
	return is_array($token) ? (string) $token[1] : (string) $token;
}

/**
 * @param array $tokens
 * @param int   $from
 *
 * @return int|null
 */
function wsklad_settings_next_significant(array $tokens, int $from)
{
	$count = count($tokens);

	for($index = $from; $index < $count; $index++)
	{
		if(wsklad_settings_is_significant($tokens[$index]))
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
function wsklad_settings_previous_significant(array $tokens, int $from)
{
	for($index = $from; $index >= 0; $index--)
	{
		if(wsklad_settings_is_significant($tokens[$index]))
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
function wsklad_settings_is_significant($token): bool
{
	if(is_string($token))
	{
		return true;
	}

	return !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
}

/**
 * The index of the `]` closing the `[` at $open.
 *
 * @param array $tokens
 * @param int   $open
 *
 * @return int|null
 */
function wsklad_settings_matching_bracket(array $tokens, int $open)
{
	$depth  = 0;
	$count  = count($tokens);

	for($index = $open; $index < $count; $index++)
	{
		if(!wsklad_settings_is_significant($tokens[$index]))
		{
			continue;
		}

		$text = wsklad_settings_token_text($tokens[$index]);

		if('[' === $text)
		{
			$depth++;

			continue;
		}

		if(']' === $text)
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

/**
 * @param string $literal
 *
 * @return string
 */
function wsklad_settings_unquote(string $literal): string
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
 * @param array $declared
 * @param array $preserved
 * @param array $reads
 * @param array $dead
 * @param array $undeclared
 * @param array $options
 */
function wsklad_settings_print_table(array $declared, array $preserved, array $reads, array $dead, array $undeclared, array $options)
{
	if($options['quiet'] || $options['only_dead'])
	{
		return;
	}

	$rows = [];

	foreach($declared as $key => $data)
	{
		if(isset($dead[$key]))
		{
			$status = 'DEAD';
		}
		elseif(WSKLAD_SETTINGS_TITLE_TYPE === $data['type'])
		{
			$status = 'heading';
		}
		else
		{
			$status = 'ok';
		}

		$reads_for_key = isset($reads[$key]) ? $reads[$key] : [];

		$rows[] =
		[
			$status,
			$key,
			$data['origin'],
			$data['type'],
			(string) count($reads_for_key),
			implode('  ', $reads_for_key),
		];
	}

	foreach($preserved as $key => $origin)
	{
		$reads_for_key = isset($reads[$key]) ? $reads[$key] : [];

		$rows[] =
		[
			'preserved',
			$key,
			$origin,
			'(removed from UI)',
			(string) count($reads_for_key),
			implode('  ', $reads_for_key),
		];
	}

	foreach($undeclared as $key)
	{
		$rows[] =
		[
			'UNDECLARED',
			$key,
			'-',
			'-',
			(string) count($reads[$key]),
			implode('  ', $reads[$key]),
		];
	}

	$headers = ['STATUS', 'KEY', 'DECLARED AT', 'TYPE', 'N', 'READ AT'];
	$widths  = [];

	foreach($headers as $column => $header)
	{
		$widths[$column] = strlen($header);
	}

	foreach($rows as $row)
	{
		foreach($row as $column => $cell)
		{
			$widths[$column] = max($widths[$column], strlen($cell));
		}
	}

	echo "\nWSKLAD settings surface\n";
	echo str_repeat('-', 118) . "\n";

	foreach($rows as $row)
	{
		$line = '';

		foreach($row as $column => $cell)
		{
			$line .= str_pad($cell, $widths[$column] + 2);
		}

		echo rtrim($line) . "\n";
	}

	echo str_repeat('-', 118) . "\n";
	echo sprintf
	(
		"%d declared field(s), %d read key(s), %d heading(s), %d preserved, %d dead, %d read but undeclared.\n",
		count($declared),
		count($reads),
		wsklad_settings_count_status($rows, 'heading'),
		count($preserved),
		count($dead),
		count($undeclared)
	);
}

/**
 * @param array  $rows
 * @param string $status
 *
 * @return int
 */
function wsklad_settings_count_status(array $rows, string $status): int
{
	$count = 0;

	foreach($rows as $row)
	{
		if($status === $row[0])
		{
			$count++;
		}
	}

	return $count;
}
