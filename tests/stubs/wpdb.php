<?php namespace Wsklad\Testing;

defined('ABSPATH') || exit;

/**
 * FakeWpdb
 *
 * An in-memory stand-in for `$wpdb`.
 *
 * The point is not to emulate MySQL. It is to be able to assert on the SQL
 * string that reaches the database layer, which is the only place a
 * parameterisation bug becomes real. `prepare()` therefore does genuine
 * placeholder substitution with real quoting, and every query handed to it is
 * recorded, so a test can say "this is the statement that was executed" instead
 * of "this function was called".
 *
 * Rows returned by `get_results()` / `get_row()` are whatever the test set: the
 * storage layer's job is to build the right query, and a fake that invented rows
 * would only test the fake.
 *
 * @package Wsklad\Tests
 */
final class FakeWpdb
{
	/**
	 * @var string
	 */
	public $base_prefix = 'wp_';

	/**
	 * @var string
	 */
	public $prefix = 'wp_';

	/**
	 * @var int
	 */
	public $insert_id = 0;

	/**
	 * @var string
	 */
	public $last_error = '';

	/**
	 * @var int
	 */
	public $last_query = 0;

	/**
	 * Every prepared statement, in order.
	 *
	 * @var string[]
	 */
	public $queries = [];

	/**
	 * Every statement handed to prepare(), with its arguments.
	 *
	 * @var array<int, array{query: string, args: array}>
	 */
	public $prepared = [];

	/**
	 * @var array<int, array{table: string, data: array}>
	 */
	public $inserts = [];

	/**
	 * @var array<int, array{table: string, data: array, where: array}>
	 */
	public $updates = [];

	/**
	 * @var array<int, array{table: string, where: array}>
	 */
	public $deletes = [];

	/**
	 * Value the next get_var() returns.
	 *
	 * @var mixed
	 */
	public $var = null;

	/**
	 * Value the next get_row() returns.
	 *
	 * @var mixed
	 */
	public $row = null;

	/**
	 * Value the next get_results() returns.
	 *
	 * @var mixed
	 */
	public $results = [];

	/**
	 * Charset/collate clause.
	 *
	 * @return string
	 */
	public function get_charset_collate()
	{
		return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
	}

	/**
	 * Substitute `%s`, `%d` and `%f` in a query, quoting string values.
	 *
	 * The behaviour mirrors `$wpdb::prepare()`: one placeholder consumes one
	 * argument, strings are single-quoted and escaped, and a surplus placeholder
	 * is left in place rather than silently dropped — a query still containing
	 * `%s` after prepare() is a bug in the caller, and hiding it here would hide
	 * it in production too.
	 *
	 * @param string $query
	 * @param mixed  ...$args
	 *
	 * @return string
	 */
	public function prepare($query, ...$args)
	{
		$this->prepared[] = ['query' => (string) $query, 'args' => $args];

		// A single array argument is the argument list, exactly as $wpdb does it.
		if(1 === count($args) && is_array($args[0]))
		{
			$args = array_values($args[0]);
		}

		$index = 0;

		$prepared = preg_replace_callback
		(
			'/%[sdfF]/',
			function (array $match) use (&$index, $args)
			{
				$value = array_key_exists($index, $args) ? $args[$index] : null;
				$index++;

				if('%d' === $match[0])
				{
					return (string) (int) $value;
				}

				if('%f' === $match[0] || '%F' === $match[0])
				{
					return (string) (float) $value;
				}

				return "'" . $this->escape((string) $value) . "'";
			},
			(string) $query
		);

		return (string) $prepared;
	}

	/**
	 * Escape a value for a SQL string literal.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function esc_like($value)
	{
		return addcslashes((string) $value, '_%\\');
	}

	/**
	 * @param string $query
	 *
	 * @return int
	 */
	public function query($query)
	{
		$this->queries[] = (string) $query;
		$this->last_query = count($this->queries);

		return 1;
	}

	/**
	 * @param string $query
	 *
	 * @return mixed
	 */
	public function get_var($query = null)
	{
		if(!is_null($query))
		{
			$this->queries[] = (string) $query;
		}

		return $this->var;
	}

	/**
	 * @param string $query
	 *
	 * @return mixed
	 */
	public function get_row($query = null, $output = OBJECT)
	{
		if(!is_null($query))
		{
			$this->queries[] = (string) $query;
		}

		return $this->row;
	}

	/**
	 * @param string $query
	 * @param string $output
	 *
	 * @return mixed
	 */
	public function get_results($query = null, $output = OBJECT)
	{
		if(!is_null($query))
		{
			$this->queries[] = (string) $query;
		}

		return $this->results;
	}

	/**
	 * @param string $table
	 * @param array  $data
	 * @param array  $format
	 *
	 * @return int|false
	 */
	public function insert($table, $data, $format = null)
	{
		$this->inserts[] = ['table' => (string) $table, 'data' => $data];

		$this->insert_id = 0 === $this->insert_id ? 1 : $this->insert_id + 1;

		return 1;
	}

	/**
	 * @param string $table
	 * @param array  $data
	 * @param array  $where
	 * @param mixed  $format
	 * @param mixed  $where_format
	 *
	 * @return int|false
	 */
	public function update($table, $data, $where, $format = null, $where_format = null)
	{
		$this->updates[] = ['table' => (string) $table, 'data' => $data, 'where' => $where];

		return 1;
	}

	/**
	 * @param string $table
	 * @param array  $where
	 * @param mixed  $where_format
	 *
	 * @return int|false
	 */
	public function delete($table, $where, $where_format = null)
	{
		$this->deletes[] = ['table' => (string) $table, 'where' => $where];

		return 1;
	}

	/**
	 * @param string $table
	 * @param array  $args
	 *
	 * @return int|false
	 */
	public function replace($table, $data, $format = null)
	{
		return $this->insert($table, $data, $format);
	}

	/**
	 * Forget every recorded query and every queued result.
	 *
	 * @return void
	 */
	public function reset()
	{
		$this->queries  = [];
		$this->prepared = [];
		$this->inserts  = [];
		$this->updates  = [];
		$this->deletes  = [];
		$this->insert_id = 0;
		$this->last_error = '';
		$this->var      = null;
		$this->row      = null;
		$this->results  = [];
	}

	/**
	 * The last statement that was run.
	 *
	 * @return string
	 */
	public function lastQuery(): string
	{
		return empty($this->queries) ? '' : (string) end($this->queries);
	}

	/**
	 * Escape a string for a SQL literal.
	 *
	 * `addslashes()` is not a complete MySQL escape, and the test suite says so:
	 * this protects against the classic `O'Reilly` break-out, which is what a
	 * parameterisation test needs to demonstrate. The real escaping is
	 * `mysqli_real_escape_string()`, and it is `prepare()` that is supposed to
	 * make it unnecessary.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	private function escape($value)
	{
		return str_replace
		(
			['\\', "\0", "\n", "\r", "'", '"', "\x1a"],
			['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
			(string) $value
		);
	}
}
