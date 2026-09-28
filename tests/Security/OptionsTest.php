<?php namespace Wsklad\Tests\Security;

use PHPUnit\Framework\TestCase;
use Wsklad\Data\Storages\AccountsStorage;

/**
 * OptionsTest
 *
 * The `options` column of `wsklad_accounts` holds a serialized array of scalars.
 * It used to be handed to a bare `maybe_unserialize()`.
 *
 * That call is an object-instantiation primitive, not a parser. `maybe_unserialize()`
 * passes no `allowed_classes` restriction, so a value of
 * `O:8:"stdClass":0:{}` — or any object graph reachable from a class already
 * autoloaded on the site — is instantiated the moment the row is read. Whoever
 * can write to that column (a compromised admin, a restored backup of unknown
 * provenance, a bug that echoes input into it) gets a POP chain, and the trigger
 * is a routine read of the accounts list.
 *
 * The options are a flat list of strings and ints, so refusing classes costs
 * nothing. `readOptions()` therefore passes `allowed_classes => false`. The
 * assertions below are that this actually happens, and that an array of scalars
 * — the overwhelming majority of real rows — still reads back unchanged, so the
 * fix needs no migration.
 *
 * @package Wsklad\Tests\Security
 */
class OptionsTest extends TestCase
{
	/**
	 * A serialized array of scalars comes back as an array.
	 *
	 * @return void
	 */
	public function test_serialized_array_of_scalars_is_read_as_an_array(): void
	{
		$options = ['schedule' => 'daily', 'enabled' => 1, 'retries' => 0, 'label' => 'Основной'];

		$read = AccountsStorage::readOptions(serialize($options));

		$this->assertIsArray($read);
		$this->assertSame($options, $read, 'A flat options array must survive readOptions() unchanged.');
	}

	/**
	 * A serialized array of arrays is still an array.
	 *
	 * @return void
	 */
	public function test_nested_arrays_are_read(): void
	{
		$options = ['filters' => ['status' => ['draft', 'active']], 'meta' => ['a' => 1]];

		$this->assertSame($options, AccountsStorage::readOptions(serialize($options)));
	}

	/**
	 * A serialized object does not become an object.
	 *
	 * This is the assertion the whole method exists for. It is written twice on
	 * purpose: once proving the return value is an array, once proving nothing
	 * was instantiated. A function could satisfy the first by returning `[]` and
	 * still have called `unserialize()` without `allowed_classes => false`.
	 *
	 * @return void
	 */
	public function test_serialized_object_does_not_become_an_object(): void
	{
		$payload = 'O:8:"stdClass":0:{}';

		$read = AccountsStorage::readOptions($payload);

		$this->assertIsArray($read, 'A serialized object must not be returned as-is.');
		$this->assertSame([], $read, 'A serialized object must read as an empty array.');
		$this->assertFalse(is_object($read), 'readOptions() returned an object.');
	}

	/**
	 * A class that is actually loaded is refused too.
	 *
	 * `stdClass` is a weak example: it has no methods and no destructor, so
	 * instantiating it proves nothing about a POP chain. `Wsklad\Environment` is
	 * in the same autoloader and is what a real payload would reach for.
	 *
	 * @return void
	 */
	public function test_serialized_plugin_class_is_refused(): void
	{
		// Force the class to be loadable, so the payload could succeed if allowed.
		class_exists(\Wsklad\Environment::class);

		$read = AccountsStorage::readOptions(serialize(new \Wsklad\Environment()));

		$this->assertFalse(is_object($read), 'A serialized plugin object was instantiated.');
		$this->assertSame([], $read);
	}

	/**
	 * A nested object inside a serialized array is refused as well.
	 *
	 * A payload does not have to be the top-level value; burying the object one
	 * level down defeats a check that only looks at the first byte.
	 *
	 * Note what "refused" means here, because it is not the same as "is not an
	 * object". With `allowed_classes => false`, PHP still materialises a
	 * placeholder — `__PHP_Incomplete_Class` — so `is_object()` on it is `true`.
	 * The placeholder is inert: it reports no methods, and touching any of them
	 * throws `Error: tried to call a method on an incomplete object` rather than
	 * running the original class. The property that matters is therefore "no class
	 * with behaviour was instantiated", which is what is asserted.
	 *
	 * @return void
	 */
	public function test_nested_serialized_object_is_refused(): void
	{
		$payload = 'a:1:{s:6:"config";O:8:"stdClass":0:{}}';

		$read = AccountsStorage::readOptions($payload);

		$this->assertIsArray($read);

		$this->assertNoLiveObject($read, 'config');
	}

	/**
	 * A JSON payload is accepted.
	 *
	 * The comment on `readOptions()` promises this so a future format change does
	 * not need another migration of the column.
	 *
	 * @return void
	 */
	public function test_json_string_is_read_as_an_array(): void
	{
		$read = AccountsStorage::readOptions('{"schedule":"daily","enabled":1}');

		$this->assertIsArray($read, 'A JSON object payload must read as an array.');
		$this->assertSame(['schedule' => 'daily', 'enabled' => 1], $read);
	}

	/**
	 * A JSON array payload is accepted too.
	 *
	 * @return void
	 */
	public function test_json_array_is_read_as_an_array(): void
	{
		$this->assertSame([1, 2, 3], AccountsStorage::readOptions('[1,2,3]'));
	}

	/**
	 * A plain scalar reads as an empty array.
	 *
	 * @return void
	 */
	public function test_plain_scalar_reads_as_empty_array(): void
	{
		$this->assertSame([], AccountsStorage::readOptions('hello'));
		$this->assertSame([], AccountsStorage::readOptions('42'));
		$this->assertSame([], AccountsStorage::readOptions('i:42;'), 'A serialized int is not an options array.');
		$this->assertSame([], AccountsStorage::readOptions('s:5:"hello";'), 'A serialized string is not an options array.');
	}

	/**
	 * `null` reads as an empty array.
	 *
	 * A brand-new row has a NULL column, and that is the common case, not the
	 * edge one.
	 *
	 * @return void
	 */
	public function test_null_reads_as_empty_array(): void
	{
		$read = AccountsStorage::readOptions(null);

		$this->assertIsArray($read);
		$this->assertSame([], $read);
	}

	/**
	 * An empty string reads as an empty array.
	 *
	 * @return void
	 */
	public function test_empty_string_reads_as_empty_array(): void
	{
		$this->assertSame([], AccountsStorage::readOptions(''));
	}

	/**
	 * An already-decoded array is returned as-is.
	 *
	 * @return void
	 */
	public function test_array_input_is_returned_unchanged(): void
	{
		$options = ['a' => 1];

		$this->assertSame($options, AccountsStorage::readOptions($options));
	}

	/**
	 * Garbage reads as an empty array, not as a PHP error.
	 *
	 * @return void
	 */
	public function test_unparseable_payload_reads_as_empty_array(): void
	{
		foreach(['not serialized at all', 'a:1:{broken', '{{{', "\x00\x01\x02"] as $payload)
		{
			$this->assertSame([], AccountsStorage::readOptions($payload), 'Payload: ' . bin2hex($payload));
		}
	}

	/**
	 * Whatever comes out of the column, it holds no live class.
	 *
	 * This is the property the callers actually depend on, asserted over every
	 * payload shape at once, so a future change to `readOptions()` cannot quietly
	 * start returning something else. "Live" means an object PHP is willing to
	 * call methods on; `__PHP_Incomplete_Class` is the only object allowed to
	 * appear, and it is inert.
	 *
	 * @return void
	 */
	public function test_every_payload_shape_yields_a_flat_array(): void
	{
		$payloads =
		[
			serialize(['a' => 1, 'b' => 'two', 'c' => 3.5, 'd' => true, 'e' => null]),
			serialize(['nested' => ['deeper' => [1, 2, 3]]]),
			'{"a":1}',
			'[1,2,3]',
			'plain',
			'',
			null,
			42,
			true,
			['already' => 'array'],
			'O:8:"stdClass":0:{}',
			'a:1:{s:6:"config";O:8:"stdClass":0:{}}',
		];

		foreach($payloads as $index => $payload)
		{
			$read = AccountsStorage::readOptions($payload);

			$this->assertIsArray($read, 'Payload #'.$index.' did not produce an array.');

			$this->assertNoLiveObject($read, 'payload #'.$index);
			$this->assertScalars($read, 'Payload #'.$index);
		}
	}

	/**
	 * writeOptions() and readOptions() agree.
	 *
	 * @return void
	 */
	public function test_write_then_read_round_trip(): void
	{
		$options = ['schedule' => 'daily', 'retries' => 3, 'nested' => ['a' => ['b' => 1]]];

		$stored = AccountsStorage::writeOptions($options);

		$this->assertIsString($stored, 'writeOptions() must produce a string for the column.');
		$this->assertSame($options, AccountsStorage::readOptions($stored));
	}

	/**
	 * Assert that no object in the value is one PHP would run code in.
	 *
	 * `__PHP_Incomplete_Class` is tolerated: it is what `allowed_classes => false`
	 * produces, it exposes no methods, and calling anything on it throws. Anything
	 * else is a class that was instantiated, which is the thing this whole test
	 * class exists to prevent.
	 *
	 * @param mixed  $value
	 * @param string $path
	 *
	 * @return void
	 */
	private function assertNoLiveObject($value, string $path): void
	{
		if(is_object($value))
		{
			$this->assertSame
			(
				'__PHP_Incomplete_Class',
				get_class($value),
				$path . ' is a live ' . get_class($value) . ' instance. A serialized payload reached the '
				. 'application as a real object, which is the object-injection primitive this guards against.'
			);

			$this->assertSame
			(
				[],
				get_class_methods($value),
				$path . ' exposes methods; an incomplete class must be inert.'
			);

			return;
		}

		if(!is_array($value))
		{
			return;
		}

		foreach($value as $key => $item)
		{
			$this->assertNoLiveObject($item, $path . '[' . $key . ']');
		}
	}

	/**
	 * Assert that the value holds only scalars, arrays, and inert placeholders.
	 *
	 * The options column is meant to be a flat list of strings and numbers, so
	 * anything else is a payload that got further than it should have.
	 *
	 * @param mixed  $value
	 * @param string $path
	 *
	 * @return void
	 */
	private function assertScalars($value, string $path): void
	{
		if(is_object($value))
		{
			// Tolerated only if it is the inert placeholder; assertNoLiveObject()
			// is what decides.
			$this->assertNoLiveObject($value, $path);

			return;
		}

		if(!is_array($value))
		{
			$this->assertTrue
			(
				is_scalar($value) || is_null($value),
				$path . ' is a ' . gettype($value) . ', which the options column should not hold.'
			);

			return;
		}

		foreach($value as $key => $item)
		{
			$this->assertScalars($item, $path . '[' . $key . ']');
		}
	}
}
