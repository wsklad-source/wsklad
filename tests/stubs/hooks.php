<?php namespace Wsklad\Testing;

defined('ABSPATH') || exit;

/**
 * Hooks
 *
 * A working re-implementation of the WordPress hook system, small enough to read
 * in one sitting and real enough that a test can assert on what actually fired.
 *
 * Why not mock the four functions? Because a mock that returns `$value` and
 * asserts "was called once" cannot tell a plugin that fires `wsklad_foo` twice
 * from one that fires it once. That difference is invisible in production and
 * obvious here, which is exactly what a test is for: two subscribers both run
 * the callback, and the second one sees state the first already changed.
 *
 * State lives in static properties so a test can start from a known-empty
 * registry. `reset()` is called by `WordPress::reset()` in `setUp()`.
 *
 * @package Wsklad\Tests
 */
final class Hooks
{
	/**
	 * hook name => priority => list of registrations.
	 *
	 * @var array<string, array<int, array<int, array{id: string, callback: callable, accepted_args: int}>>>
	 */
	private static $registry = [];

	/**
	 * Ordered log of everything that was fired.
	 *
	 * @var array<int, array{hook: string, kind: string, args: array}>
	 */
	private static $fired = [];

	/**
	 * Register a callback.
	 *
	 * @param string   $hook
	 * @param callable $callback
	 * @param int      $priority
	 * @param int      $accepted_args
	 *
	 * @return bool
	 */
	public static function add(string $hook, $callback, int $priority = 10, int $accepted_args = 1): bool
	{
		if(!is_callable($callback))
		{
			return false;
		}

		self::$registry[$hook][$priority][] =
		[
			'id'            => self::identify($callback),
			'callback'      => $callback,
			'accepted_args' => $accepted_args,
		];

		return true;
	}

	/**
	 * Remove a callback.
	 *
	 * @param string   $hook
	 * @param callable $callback
	 * @param int      $priority
	 *
	 * @return bool
	 */
	public static function remove(string $hook, $callback, int $priority = 10): bool
	{
		$id = self::identify($callback);

		if(!isset(self::$registry[$hook][$priority]))
		{
			return false;
		}

		$removed = false;

		foreach(self::$registry[$hook][$priority] as $index => $registration)
		{
			if($registration['id'] === $id)
			{
				unset(self::$registry[$hook][$priority][$index]);
				$removed = true;
			}
		}

		if(empty(self::$registry[$hook][$priority]))
		{
			unset(self::$registry[$hook][$priority]);
		}

		return $removed;
	}

	/**
	 * Whether anything is registered.
	 *
	 * @param string        $hook
	 * @param callable|bool $callback
	 *
	 * @return bool|int
	 */
	public static function has(string $hook, $callback = false)
	{
		if(empty(self::$registry[$hook]))
		{
			return false;
		}

		if(false === $callback)
		{
			return true;
		}

		$id = self::identify($callback);

		foreach(self::$registry[$hook] as $priority => $registrations)
		{
			foreach($registrations as $registration)
			{
				if($registration['id'] === $id)
				{
					return $priority;
				}
			}
		}

		return false;
	}

	/**
	 * Run a filter chain, threading the value through every callback.
	 *
	 * @param string $hook
	 * @param mixed  $value
	 * @param array  $args
	 *
	 * @return mixed
	 */
	public static function filter(string $hook, $value, array $args = [])
	{
		self::$fired[] = ['hook' => $hook, 'kind' => 'filter', 'args' => array_merge([$value], $args)];

		foreach(self::ordered($hook) as $registration)
		{
			$passed = array_slice(array_merge([$value], $args), 0, max(1, $registration['accepted_args']));

			$value = call_user_func_array($registration['callback'], $passed);
		}

		return $value;
	}

	/**
	 * Run an action. The return value of the last callback is discarded, exactly
	 * as WordPress does.
	 *
	 * @param string $hook
	 * @param array  $args
	 *
	 * @return void
	 */
	public static function action(string $hook, array $args = [])
	{
		self::$fired[] = ['hook' => $hook, 'kind' => 'action', 'args' => $args];

		foreach(self::ordered($hook) as $registration)
		{
			call_user_func_array
			(
				$registration['callback'],
				array_slice($args, 0, $registration['accepted_args'])
			);
		}
	}

	/**
	 * Everything that has been fired, in order.
	 *
	 * @return array<int, array{hook: string, kind: string, args: array}>
	 */
	public static function fired(): array
	{
		return self::$fired;
	}

	/**
	 * How many times a hook has been fired.
	 *
	 * @param string|null $hook Hook name, or null for the total.
	 *
	 * @return int
	 */
	public static function count(?string $hook = null): int
	{
		if(is_null($hook))
		{
			return count(self::$fired);
		}

		$count = 0;

		foreach(self::$fired as $entry)
		{
			if($entry['hook'] === $hook)
			{
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Drop the registry and the log.
	 *
	 * @return void
	 */
	public static function reset()
	{
		self::$registry = [];
		self::$fired    = [];
	}

	/**
	 * Registrations for a hook, sorted by priority then insertion order.
	 *
	 * @param string $hook
	 *
	 * @return array<int, array{id: string, callback: callable, accepted_args: int}>
	 */
	private static function ordered(string $hook): array
	{
		if(empty(self::$registry[$hook]))
		{
			return [];
		}

		$priorities = self::$registry[$hook];
		ksort($priorities, SORT_NUMERIC);

		$flat = [];

		foreach($priorities as $registrations)
		{
			foreach($registrations as $registration)
			{
				$flat[] = $registration;
			}
		}

		return $flat;
	}

	/**
	 * A stable identity for a callable.
	 *
	 * Two closures that do the same thing are still two registrations, which is
	 * how WordPress behaves; a string function name registered twice at the same
	 * priority is the same registration, because it is literally the same code.
	 *
	 * @param callable $callback
	 *
	 * @return string
	 */
	private static function identify($callback): string
	{
		if(is_string($callback))
		{
			return 'fn:' . strtolower($callback);
		}

		if(is_array($callback) && count($callback) === 2)
		{
			$target = is_object($callback[0]) ? 'obj:' . spl_object_id($callback[0]) : (string) $callback[0];

			return 'm:' . $target . '::' . (string) $callback[1];
		}

		if($callback instanceof \Closure)
		{
			return 'c:' . spl_object_id($callback);
		}

		if(is_object($callback))
		{
			return 'i:' . get_class($callback) . '#' . spl_object_id($callback);
		}

		return 'x:' . md5(serialize($callback));
	}
}
