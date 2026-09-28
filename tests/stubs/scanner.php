<?php namespace Wsklad\Testing;

defined('ABSPATH') || exit;

/**
 * SourceScanner
 *
 * A tiny static analyser used by the contract tests.
 *
 * It answers three questions about the plugin's own source, none of which can be
 * answered by running the plugin (there is no WordPress on a CI runner and no
 * browser to click through):
 *
 *  1. Which view files does `getView()` name, and do they exist?
 *  2. Which hook names are fired, and are any of them fired twice?
 *  3. Which functions mutate state, and do they verify a nonce?
 *
 * It works on the token stream rather than on regular expressions over raw text.
 * That is not gold plating: `do_action($name)`, `apply_filters("$prefix_x", …)`
 * and a hook name inside a doc comment all look identical to a regex, and all
 * three would produce false hooks. A token stream knows the difference.
 *
 * The class deliberately depends on nothing — no WordPress, no autoloader — so
 * that the same code can be exercised from a plain `php` process.
 *
 * @package Wsklad\Tests
 */
final class SourceScanner
{
	/**
	 * Directories that must never be walked.
	 *
	 * `vendor` is not a preference here. Scanning it would both be useless (the
	 * plugin does not own it) and forbidden by this repository's own tooling rules.
	 *
	 * @var string[]
	 */
	private static $ignored_dirs =
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
	 * Project root, with a trailing directory separator.
	 *
	 * @var string
	 */
	private $root;

	/**
	 * @param string $root Project root directory.
	 */
	public function __construct(string $root)
	{
		$this->root = rtrim($root, '/\\') . DIRECTORY_SEPARATOR;
	}

	/**
	 * Absolute project root.
	 *
	 * @return string
	 */
	public function getRoot(): string
	{
		return $this->root;
	}

	/**
	 * Collect files under the given project-relative directories.
	 *
	 * @param string[] $directories Relative paths, e.g. `['src', 'views']`.
	 * @param string[] $extensions  Extensions without the dot, e.g. `['php']`.
	 *
	 * @return string[] Relative path => absolute path, sorted by relative path.
	 */
	public function collect(array $directories, array $extensions = ['php']): array
	{
		$found = [];

		foreach($directories as $directory)
		{
			$absolute = $this->root . str_replace('/', DIRECTORY_SEPARATOR, trim($directory, '/\\'));

			if(!is_dir($absolute))
			{
				continue;
			}

			$iterator = new \RecursiveIteratorIterator
			(
				new \RecursiveDirectoryIterator($absolute, \FilesystemIterator::SKIP_DOTS)
			);

			/** @var \SplFileInfo $file */
			foreach($iterator as $file)
			{
				if(!$file->isFile())
				{
					continue;
				}

				$path     = $this->normalize($file->getPathname());
				$relative = substr($path, strlen($this->root));

				if($this->isIgnored($relative))
				{
					continue;
				}

				$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

				if(!in_array($extension, $extensions, true))
				{
					continue;
				}

				$found[$relative] = $path;
			}
		}

		ksort($found);

		return $found;
	}

	/**
	 * Every literal `getView('…')` target in the scanned source.
	 *
	 * @param string[] $directories Relative directories to walk.
	 *
	 * @return array<string, array<int, array{file: string, line: int}>> view path => occurrences
	 */
	public function findViews(array $directories): array
	{
		$views = [];

		foreach($this->collect($directories) as $relative => $absolute)
		{
			foreach($this->callsOf($absolute, ['getView']) as $call)
			{
				if(is_null($call['first_argument']))
				{
					continue;
				}

				$view = $call['first_argument'];

				if('' === $view || false !== strpos($view, '..'))
				{
					continue;
				}

				$views[$view][] =
				[
					'file' => $relative,
					'line' => $call['line'],
				];
			}
		}

		ksort($views);

		return $views;
	}

	/**
	 * Every literal hook name passed to `do_action()` / `apply_filters()`.
	 *
	 * @param string[] $directories Relative directories to walk.
	 *
	 * @return array<string, array<int, array{file: string, line: int, kind: string}>> hook name => occurrences
	 */
	public function findHooks(array $directories): array
	{
		$hooks = [];

		foreach($this->collect($directories) as $relative => $absolute)
		{
			foreach($this->callsOf($absolute, ['do_action', 'apply_filters']) as $call)
			{
				if(is_null($call['first_argument']))
				{
					// A dynamic name cannot be checked; counting it would be a guess.
					continue;
				}

				$name = $call['first_argument'];

				if(!preg_match('/^[a-z0-9_]+$/i', $name))
				{
					continue;
				}

				$hooks[$name][] =
				[
					'file' => $relative,
					'line' => $call['line'],
					'kind' => $call['function'],
				];
			}
		}

		ksort($hooks);

		return $hooks;
	}

	/**
	 * Every named function declaration, with its body text.
	 *
	 * Closures and arrow functions are skipped: they have no name to key on, and
	 * a closure's body belongs to the enclosing method as far as a nonce check
	 * is concerned.
	 *
	 * @param string[] $directories Relative directories to walk.
	 *
	 * @return array<string, array<int, array{name: string, line: int, body: string}>> relative file => declarations
	 */
	public function findFunctions(array $directories): array
	{
		$functions = [];

		foreach($this->collect($directories) as $relative => $absolute)
		{
			$found = $this->functionsIn($absolute);

			if(!empty($found))
			{
				$functions[$relative] = $found;
			}
		}

		ksort($functions);

		return $functions;
	}

	/**
	 * Locate calls of the given function names and read their first argument.
	 *
	 * A call only counts when the first argument is a single literal string. A
	 * variable, a concatenation or an interpolated double-quoted string is
	 * reported as `null` rather than guessed at.
	 *
	 * @param string   $absolute Absolute file path.
	 * @param string[] $names    Function names to look for.
	 *
	 * @return array<int, array{function: string, line: int, first_argument: string|null}>
	 */
	public function callsOf(string $absolute, array $names): array
	{
		$tokens = $this->tokens($absolute);
		$total  = count($tokens);
		$found  = [];

		for($i = 0; $i < $total; $i++)
		{
			$token = $tokens[$i];

			if(!is_array($token) || T_STRING !== $token[0])
			{
				continue;
			}

			$function = strtolower($token[1]);
			$wanted   = array_map('strtolower', $names);

			if(!in_array($function, $wanted, true))
			{
				continue;
			}

			// Reject static calls and declarations. A method call is exactly what we
			// want here: the views are reached as `wsklad()->views()->getView(…)`.
			$previous = $this->previousSignificant($tokens, $i);

			if(!is_null($previous) && is_array($previous) && in_array($previous[0], [T_DOUBLE_COLON, T_FUNCTION], true))
			{
				continue;
			}

			$open = $this->nextSignificantIndex($tokens, $i);

			if(is_null($open))
			{
				continue;
			}

			if('(' !== $this->tokenText($tokens[$open]))
			{
				continue;
			}

			$found[] =
			[
				'function'       => $function,
				'line'           => $token[2],
				'first_argument' => $this->firstStringArgument($tokens, $open),
			];
		}

		return $found;
	}

	/**
	 * Named function declarations in one file.
	 *
	 * @param string $absolute Absolute file path.
	 *
	 * @return array<int, array{name: string, line: int, body: string}>
	 */
	private function functionsIn(string $absolute): array
	{
		$tokenized = $this->tokenize($absolute);
		$tokens    = $tokenized['tokens'];
		$offsets   = $tokenized['offsets'];
		$source    = $tokenized['source'];
		$total     = count($tokens);
		$found     = [];

		for($i = 0; $i < $total; $i++)
		{
			$token = $tokens[$i];

			if(!is_array($token) || T_FUNCTION !== $token[0])
			{
				continue;
			}

			$name_index = $this->nextSignificantIndex($tokens, $i);

			// `function (` — a closure. No name to report.
			if(is_null($name_index))
			{
				continue;
			}

			$name = $tokens[$name_index];

			if(!is_array($name) || T_STRING !== $name[0])
			{
				continue;
			}

			// Skip the parameter list: `function foo(int $a, string $b): array {`.
			$open = $this->bodyStart($tokens, $name_index);

			if(is_null($open))
			{
				continue;
			}

			$open_text = $this->tokenText($tokens[$open]);

			// Abstract or interface method: `function foo();` — no body at all.
			if(';' === $open_text)
			{
				$found[] = ['name' => $name[1], 'line' => $name[2], 'body' => ''];
				continue;
			}

			if('{' !== $open_text)
			{
				continue;
			}

			$close = $this->matchingBrace($tokens, $open);

			if(is_null($close))
			{
				continue;
			}

			$open_offset  = $offsets[$open];
			$close_offset = $offsets[$close];

			$found[] =
			[
				'name' => $name[1],
				'line' => $name[2],
				'body' => substr($source, $open_offset + 1, $close_offset - $open_offset - 1),
			];
		}

		return $found;
	}

	/**
	 * Read the first argument of a call, if it is a single literal string.
	 *
	 * @param array $tokens Token list.
	 * @param int   $open   Index of the `(` token.
	 *
	 * @return string|null
	 */
	private function firstStringArgument(array $tokens, int $open)
	{
		$argument = $this->nextSignificant($tokens, $open);

		if(is_null($argument) || !is_array($argument) || T_CONSTANT_ENCAPSED_STRING !== $argument[0])
		{
			return null;
		}

		// A double-quoted string with an escape or an interpolation is not a
		// constant: `"$prefix_hook"` and `"wsklad_{$x}"` must not be reported.
		if('"' === $argument[1][0] && preg_match('/(?<!\\\\)\$|(?<!\\\\)\{\$/', $argument[1]))
		{
			return null;
		}

		return $this->unquote($argument[1]);
	}

	/**
	 * Strip PHP string quoting and resolve the escapes that a hook name can contain.
	 *
	 * @param string $literal Raw token text, including quotes.
	 *
	 * @return string
	 */
	private function unquote(string $literal): string
	{
		if(strlen($literal) < 2)
		{
			return '';
		}

		$quote = $literal[0];
		$body  = substr($literal, 1, -1);

		$replacements = ['\\' === $quote ? "\\\\'" : "\\\\\"", '\\\\'];

		return str_replace($replacements, ["'", '\\'], $body);
	}

	/**
	 * Index of the token that opens a declaration body: the `{` of a real body or
	 * the `;` of an abstract/interface method.
	 *
	 * The parameter list is skipped by matching parentheses, so a default value or
	 * a return type containing braces cannot be mistaken for the body.
	 *
	 * @param array $tokens Token list.
	 * @param int   $name   Index of the function name token.
	 *
	 * @return int|null
	 */
	private function bodyStart(array $tokens, int $name)
	{
		$open = $this->nextSignificantIndex($tokens, $name);

		if(is_null($open) || '(' !== $this->tokenText($tokens[$open]))
		{
			return null;
		}

		$close = $this->matchingParenthesis($tokens, $open);

		if(is_null($close))
		{
			return null;
		}

		$total = count($tokens);

		for($i = $close + 1; $i < $total; $i++)
		{
			if(is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true))
			{
				continue;
			}

			$text = $this->tokenText($tokens[$i]);

			if('{' === $text || ';' === $text)
			{
				return $i;
			}

			// Anything else before the body means this is not a declaration we
			// can measure; bail out rather than guess.
			if(!in_array($text, [')', ':', '|', '&', '?', '\\\\'], true) && T_STRING !== (is_array($tokens[$i]) ? $tokens[$i][0] : null))
			{
				return null;
			}
		}

		return null;
	}

	/**
	 * Index of the `)` matching the `(` at `$open`.
	 *
	 * @param array $tokens Token list.
	 * @param int   $open   Index of the `(` token.
	 *
	 * @return int|null
	 */
	private function matchingParenthesis(array $tokens, int $open)
	{
		$depth = 0;
		$total = count($tokens);

		for($i = $open; $i < $total; $i++)
		{
			$text = $this->tokenText($tokens[$i]);

			if('(' === $text)
			{
				$depth++;
			}
			elseif(')' === $text)
			{
				$depth--;

				if(0 === $depth)
				{
					return $i;
				}
			}
		}

		return null;
	}

	/**
	 * Index of the `}` matching the `{` at `$open`.
	 *
	 * @param array $tokens Token list.
	 * @param int   $open   Index of the `{` token.
	 *
	 * @return int|null
	 */
	private function matchingBrace(array $tokens, int $open)
	{
		$depth  = 0;
		$total  = count($tokens);

		for($i = $open; $i < $total; $i++)
		{
			$text = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];

			if('{' === $text)
			{
				$depth++;
			}
			elseif('}' === $text)
			{
				$depth--;

				if(0 === $depth)
				{
					return $i;
				}
			}
		}

		return null;
	}

	/**
	 * Previous token that is neither whitespace nor a comment.
	 *
	 * @param array $tokens Token list.
	 * @param int   $from   Index to walk backwards from.
	 *
	 * @return array|string|null
	 */
	private function previousSignificant(array $tokens, int $from)
	{
		$index = $this->previousSignificantIndex($tokens, $from);

		return is_null($index) ? null : $tokens[$index];
	}

	/**
	 * Index of the previous token that is neither whitespace nor a comment.
	 *
	 * @param array $tokens Token list.
	 * @param int   $from   Index to walk backwards from.
	 *
	 * @return int|null
	 */
	private function previousSignificantIndex(array $tokens, int $from)
	{
		for($i = $from - 1; $i >= 0; $i--)
		{
			if(is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true))
			{
				continue;
			}

			return $i;
		}

		return null;
	}

	/**
	 * Next token that is neither whitespace nor a comment.
	 *
	 * @param array $tokens Token list.
	 * @param int   $from   Index to walk forwards from.
	 *
	 * @return array|string|null
	 */
	private function nextSignificant(array $tokens, int $from)
	{
		$index = $this->nextSignificantIndex($tokens, $from);

		return is_null($index) ? null : $tokens[$index];
	}

	/**
	 * Index of the next token that is neither whitespace nor a comment.
	 *
	 * @param array $tokens Token list.
	 * @param int   $from   Index to walk forwards from.
	 *
	 * @return int|null
	 */
	private function nextSignificantIndex(array $tokens, int $from)
	{
		$total = count($tokens);

		for($i = $from + 1; $i < $total; $i++)
		{
			if(is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true))
			{
				continue;
			}

			return $i;
		}

		return null;
	}

	/**
	 * Text of a token, whether it is an array token or a single character.
	 *
	 * @param array|string $token
	 *
	 * @return string
	 */
	private function tokenText($token): string
	{
		return is_array($token) ? (string) $token[1] : (string) $token;
	}

	/**
	 * Tokenise a file, keeping the byte offset of every token.
	 *
	 * The offsets are not a convenience. `token_get_all()` reports a line number
	 * per token but no position, and a scanner that needs a function's *body*
	 * text has to slice the original source — so it has to know where each token
	 * starts. Handing `substr()` a token index instead of an offset produces a
	 * plausible-looking slice from the wrong part of the file, and every
	 * conclusion drawn from it is then wrong in a way that is very hard to see.
	 *
	 * @param string $absolute Absolute file path.
	 *
	 * @return array{tokens: array, offsets: array<int, int>, source: string}
	 */
	private function tokenize(string $absolute): array
	{
		$source = (string) @file_get_contents($absolute);
		$tokens = (string) $source === '' ? [] : @token_get_all($source);

		$offsets = [];
		$cursor  = 0;

		foreach($tokens as $index => $token)
		{
			$offsets[$index] = $cursor;
			$cursor         += strlen(is_array($token) ? (string) $token[1] : (string) $token);
		}

		return ['tokens' => $tokens, 'offsets' => $offsets, 'source' => $source];
	}

	/**
	 * @param string $absolute Absolute file path.
	 *
	 * @return array
	 */
	private function tokens(string $absolute): array
	{
		return $this->tokenize($absolute)['tokens'];
	}

	/**
	 * @param string $relative Project-relative path with `/` separators.
	 *
	 * @return bool
	 */
	private function isIgnored(string $relative): bool
	{
		$relative = str_replace('\\', '/', $relative);

		foreach(explode('/', $relative) as $segment)
		{
			if(in_array($segment, self::$ignored_dirs, true))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $path Possibly Windows-flavoured path.
	 *
	 * @return string
	 */
	private function normalize(string $path): string
	{
		return str_replace('\\', '/', $path);
	}
}
