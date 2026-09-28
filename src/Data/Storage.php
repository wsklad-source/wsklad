<?php namespace Wsklad\Data;

defined('ABSPATH') || exit;

use Wsklad\Exceptions\SchemaException;

/**
 * Storage
 *
 * @package Wsklad\Data
 */
class Storage extends \Digiom\Woplucore\Data\Storage
{
	/**
	 * @var string Unique prefix
	 */
	public $unique_prefix = 'wsklad';

	/**
	 * Contains an array of default supported data storages
	 *
	 * Format of object name => class name
	 * Example: 'key' => 'UniqueNameStorage'
	 *
	 * You can also pass something like key_<type> for codes storage and
	 * that type will be used first when available, if a store is requested like
	 * this and doesn't exist, then the store would fall back to 'key'.
	 * Ran through PREFIX `_data_storages`.
	 *
	 * @var array
	 */
	public $storages =
	[
		'account' => \Wsklad\Data\Storages\AccountsStorage::class,
	];

	/**
	 * Check the schema before handing out a storage object.
	 *
	 * Before 0.10.1 a missing table surfaced as a wpdb error printed straight into the
	 * page — on the front end, inside a JSON response, or as a PHP fatal depending on
	 * where the query happened. Now it is a typed exception the caller can catch, and
	 * `Core::ensureSchema()` normally prevents the situation from arising at all.
	 *
	 * @return void
	 *
	 * @throws SchemaException
	 */
	public function assertSchemaExists()
	{
		if(!function_exists('wsklad') || !function_exists('wsklad'))
		{
			return;
		}

		try
		{
			$schema = \wsklad()->schema();

			if($schema->isCurrent() && $schema->tablesExist())
			{
				return;
			}
		}
		catch(\Throwable $e)
		{
			// If we cannot even ask, do not block the caller.
			return;
		}

		throw new SchemaException
		(
			__('WSKLAD database tables are missing. Deactivate and activate the plugin to recreate them.', 'wsklad')
		);
	}
}
