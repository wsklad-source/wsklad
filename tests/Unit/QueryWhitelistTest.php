<?php namespace Wsklad\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wsklad\Data\Storages\AccountsStorage;

/**
 * QueryWhitelistTest
 *
 * `getData()` and `countBy()` build SQL from caller-supplied array keys, and a
 * key in `getData(['columns' => …])` is a column *name*, not a value. Names
 * cannot be bound as parameters, so the only safe handling is a whitelist:
 * `getQueryableColumns()` and `getSortableColumns()` are it.
 *
 * That makes these two lists security-relevant data, not configuration. A
 * backtick, a space, a comma or a parenthesis in an entry is not untidy — it is
 * an injection point waiting for a caller that passes user input as an array key.
 * The previous implementation interpolated the column name directly, which is
 * exactly the bug the whitelist was introduced to close, so the list itself now
 * needs the same kind of test as the code that reads it.
 *
 * The name pattern is deliberately stricter than `[a-z_]`: no digits. No column
 * in this table has one, and allowing digits would be a loosening with no
 * current benefit.
 *
 * @package Wsklad\Tests\Unit
 */
class QueryWhitelistTest extends TestCase
{
	/**
	 * @var AccountsStorage
	 */
	private $storage;

	/**
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->storage = new AccountsStorage();
	}

	/**
	 * Both whitelists are lists of strings.
	 *
	 * @return void
	 */
	public function test_both_whitelists_are_non_empty_arrays_of_strings(): void
	{
		foreach(['getQueryableColumns' => 'queryable', 'getSortableColumns' => 'sortable'] as $method => $label)
		{
			$columns = $this->storage->{$method}();

			$this->assertIsArray($columns, $label . ' must return an array.');
			$this->assertNotEmpty($columns, $label . ' must not be empty; an empty whitelist rejects every query.');

			foreach($columns as $column)
			{
				$this->assertIsString($column, $label . ' contains a non-string entry: ' . gettype($column) . '.');
			}
		}
	}

	/**
	 * The queryable whitelist is a list, not a map.
	 *
	 * A string key would survive `in_array()` and break `array_fill()`-style
	 * consumers, and the bug would only show up on the odd key.
	 *
	 * @return void
	 */
	public function test_whitelists_are_sequential_lists(): void
	{
		foreach(['getQueryableColumns', 'getSortableColumns'] as $method)
		{
			$columns = $this->storage->{$method}();

			$this->assertSame
			(
				array_keys($columns),
				range(0, count($columns) - 1),
				$method . ' is not a sequential list; it is a map.'
			);
		}
	}

	/**
	 * No entry is repeated.
	 *
	 * A duplicate in `in_array()` is harmless, but a duplicate in a list that is
	 * later turned into a `SELECT` field list produces `SELECT name, name` — and
	 * one that is used to build a `GROUP BY` produces a subtly different result
	 * set for no reason anyone can reconstruct.
	 *
	 * @return void
	 */
	public function test_whitelists_contain_no_duplicates(): void
	{
		foreach(['getQueryableColumns', 'getSortableColumns'] as $method)
		{
			$columns = $this->storage->{$method}();

			$unique  = array_unique($columns);
			$duplicates = array_values(array_diff_assoc($columns, $unique));

			$this->assertSame
			(
				[],
				$duplicates,
				$method . ' lists ' . count($duplicates) . ' duplicate column name(s): ' . implode(', ', $duplicates)
			);
		}
	}

	/**
	 * Every entry is a bare lower-case identifier.
	 *
	 * This is the assertion that would catch a compromised or careless whitelist:
	 * a name containing a backtick, a space, a comma, a parenthesis or a quote
	 * cannot come from `CREATE TABLE`, it can only come from a mistake or an
	 * injection.
	 *
	 * @return void
	 */
	public function test_whitelists_contain_only_safe_identifiers(): void
	{
		foreach(['getQueryableColumns', 'getSortableColumns'] as $method)
		{
			foreach($this->storage->{$method}() as $column)
			{
				$this->assertSame
				(
					1,
					preg_match('/^[a-z][a-z_]*$/', $column),
					$method . ' contains `' . $column . '`, which is not a bare lower-case identifier. '
					. 'A whitelist entry is concatenated into SQL, so it must be a name and nothing else.'
				);
			}
		}
	}

	/**
	 * No entry is an SQL reserved word that would need quoting.
	 *
	 * @return void
	 */
	public function test_whitelists_contain_no_sql_keywords(): void
	{
		$reserved = array_flip
		(
			[
				'select', 'from', 'where', 'table', 'order', 'group', 'by', 'join', 'on', 'and', 'or',
				'not', 'null', 'is', 'in', 'like', 'between', 'as', 'union', 'all', 'distinct', 'limit',
				'offset', 'insert', 'update', 'delete', 'drop', 'alter', 'create', 'grant', 'revoke',
				'asc', 'desc', 'having', 'set', 'values', 'into', 'case', 'when', 'then', 'else', 'end',
				'outfile', 'load_file', 'sleep', 'benchmark',
			]
		);

		foreach(['getQueryableColumns', 'getSortableColumns'] as $method)
		{
			foreach($this->storage->{$method}() as $column)
			{
				$this->assertArrayNotHasKey
				(
					$column,
					$reserved,
					$method . ' contains the SQL keyword `' . $column . '`, which would have to be quoted to be usable.'
				);
			}
		}
	}

	/**
	 * Sorting is a subset of filtering.
	 *
	 * `getData()` sorts on `getSortableColumns()` and filters on
	 * `getQueryableColumns()`. A sortable column that cannot be filtered is not a
	 * security hole, but it is a sign the two lists have drifted apart, and drift
	 * is how the "safe to sort" argument quietly stops being true.
	 *
	 * @return void
	 */
	public function test_sortable_columns_are_a_subset_of_queryable_columns(): void
	{
		$queryable = $this->storage->getQueryableColumns();
		$sortable  = $this->storage->getSortableColumns();

		$orphans = array_values(array_diff($sortable, $queryable));

		$this->assertSame
		(
			[],
			$orphans,
			'Sortable column(s) not in the queryable whitelist: ' . implode(', ', $orphans)
		);
	}

	/**
	 * Sorting is a strict subset: not every filterable column is sortable.
	 *
	 * @return void
	 */
	public function test_not_every_queryable_column_is_sortable(): void
	{
		$queryable = $this->storage->getQueryableColumns();
		$sortable  = $this->storage->getSortableColumns();

		$this->assertGreaterThan
		(
			0,
			count($queryable) - count($sortable),
			'The two whitelists are identical. A column that may be filtered on does not automatically '
			. 'have to be sortable, and the difference is what makes the ORDER BY path deliberate.'
		);
	}

	/**
	 * The columns that hold credentials are not exposed by the whitelist.
	 *
	 * `moysklad_password` and `moysklad_token` are encrypted, but a column name in
	 * a `SELECT … AS <alias>` is caller-controlled, and putting a secret column
	 * in a public whitelist invites it into a JSON response. They must be
	 * readable by the storage layer and absent from the query API.
	 *
	 * @return void
	 */
	public function test_secret_columns_are_not_in_either_whitelist(): void
	{
		$secrets = ['moysklad_password', 'moysklad_token'];

		foreach(['getQueryableColumns', 'getSortableColumns'] as $method)
		{
			foreach($secrets as $secret)
			{
				$this->assertNotContains
				(
					$secret,
					$this->storage->{$method}(),
					$method . ' exposes `' . $secret . '`, a stored credential.'
				);
			}
		}
	}

	/**
	 * The whitelist is returned as a copy.
	 *
	 * A caller that mutates the returned array would otherwise be editing the
	 * storage's own security boundary for the rest of the request.
	 *
	 * @return void
	 */
	public function test_mutating_the_returned_array_does_not_affect_the_next_call(): void
	{
		$first = $this->storage->getQueryableColumns();

		$first[] = 'injected';
		$first[0] = 'tampered';

		$second = $this->storage->getQueryableColumns();

		$this->assertNotContains('injected', $second);
		$this->assertContains('account_id', $second);
		$this->assertNotContains('tampered', $second);
	}
}
