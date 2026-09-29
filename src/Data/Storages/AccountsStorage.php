<?php namespace Wsklad\Data\Storages;

defined('ABSPATH') || exit;

use WP_Error;
use Digiom\Woplucore\Data\Abstracts\WithMetaDataStorageAbstract;
use Digiom\Woplucore\Data\Meta;
use Wsklad\Data\Abstracts\DataAbstract;
use Wsklad\Data\Entities\Account;
use Wsklad\Data\MetaQuery;
use Wsklad\Exceptions\Exception;
use Wsklad\Traits\AccountsUtilityTrait;
use Wsklad\Traits\StoredDateTrait;

/**
 * AccountsStorage
 *
 * @package Wsklad\Data\Storages
 */
class AccountsStorage extends WithMetaDataStorageAbstract
{
	use AccountsUtilityTrait;

	/**
	 * The date parser, because the columns this class reads are the plugin's own
	 * `VARCHAR` dates and the "unreadable is null, not 1970" rule has to apply where the
	 * reading happens.
	 *
	 * The parent is the vendor storage abstract, not `Data\Abstracts\DataAbstract`, so
	 * the trait has to be taken here explicitly. Duplicating the rule in two places
	 * would be worse than repeating the `use` line: one of the copies would eventually
	 * be the one nobody updates.
	 */
	use StoredDateTrait;

	/**
	 * @return string
	 */
	public function getTableName(): string
	{
		return wsklad()->database()->base_prefix . 'wsklad_accounts';
	}

	/**
	 * Read the `options` column.
	 *
	 * ⚠ Since 0.10.0 the value is no longer handed to a bare `maybe_unserialize()`.
	 * That call passes `allowed_classes` implicitly, so any writer of this column — a
	 * compromised admin account, a bug, a restored backup of unknown provenance — could
	 * instantiate arbitrary objects through a POP chain the moment the row is read.
	 * Options are a flat list of scalars, so refusing classes costs nothing and closes
	 * the primitive.
	 *
	 * Old values are still read correctly: a serialized array of scalars unserializes
	 * identically with `allowed_classes => false`, so no migration is needed.
	 *
	 * @param mixed $value
	 *
	 * @return array
	 */
	public static function readOptions($value): array
	{
		if(is_array($value))
		{
			return $value;
		}

		if(!is_string($value) || '' === $value)
		{
			return [];
		}

		// A JSON payload is accepted too, so that a future format change can be made
		// without another migration of the same column.
		$trimmed = ltrim($value);

		if('' !== $trimmed && ('{' === $trimmed[0] || '[' === $trimmed[0]))
		{
			$decoded = json_decode($value, true);

			if(is_array($decoded))
			{
				return $decoded;
			}
		}

		$unserialized = @unserialize($value, ['allowed_classes' => false]);

		if(is_array($unserialized))
		{
			return $unserialized;
		}

		// `maybe_unserialize()` would have returned the string unchanged; keep that.
		return [];
	}

	/**
	 * Serialize the `options` array for storage.
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	public static function writeOptions(array $options): string
	{
		return maybe_serialize($options);
	}

	/**
	 * Columns that may appear in a WHERE clause or an ORDER BY.
	 *
	 * Any other key is a bug or an attack; both are rejected rather than interpolated.
	 * `parseQueryConditions()` and `getData()` share this list so a column that is safe
	 * to filter on is by construction safe to sort on.
	 *
	 * @return array
	 */
	public function getQueryableColumns(): array
	{
		return
		[
			'account_id',
			'connection_type',
			'site_id',
			'user_id',
			'name',
			'status',
			'date_create',
			'date_modify',
			'date_activity',
			'wsklad_version',
			'wsklad_version_init',
			'moysklad_login',
			'moysklad_role',
			'moysklad_tariff',
			'moysklad_account_id',
		];
	}

	/**
	 * Columns the accounts list screen is allowed to sort by.
	 *
	 * @return array
	 */
	public function getSortableColumns(): array
	{
		return ['account_id', 'name', 'status', 'date_create', 'date_modify', 'date_activity', 'user_id'];
	}

	/**
	 * Encrypt a secret for storage.
	 *
	 * Falls back to the plain value when libsodium is missing, so that a host without
	 * the extension keeps working — loudly, via the admin notice, rather than by
	 * silently storing nothing.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function encryptSecret(string $value): string
	{
		if('' === $value)
		{
			return '';
		}

		$cryptography = wsklad()->cryptography();

		if(!$cryptography->isAvailable() || $cryptography->isEncrypted($value))
		{
			return $value;
		}

		$encrypted = $cryptography->encrypt($value);

		return '' === $encrypted ? $value : $encrypted;
	}

	/**
	 * Method to create a new object in the database
	 *
	 * @param Account $data Data object
	 *
	 * @throws Exception
	 */
	public function create(&$data)
	{
		if(!$data->getDateCreate('edit'))
		{
			$data->setDateCreate(time());
		}

		$insert_data =
		[
			'wsklad_version_init' => wsklad()->environment()->get('wsklad_version'),
			'wsklad_version' => wsklad()->environment()->get('wsklad_version'),
			'user_id' => $data->getUserId() ?: get_current_user_id(),
			'connection_type' => $data->getConnectionType(),
			'name' => $data->getName(),
			'status' => $data->getStatus(),
			'options' => self::writeOptions($data->getOptions()),
			'date_create' => gmdate('Y-m-d H:i:s', $data->getDateCreate('edit')->getTimestamp()),
			'date_modify' => $data->getDateModify(),
			'date_activity' => $data->getDateActivity(),
			'moysklad_login' => $data->getMoyskladLogin(),
			'moysklad_password' => $this->encryptSecret($data->getMoyskladPassword()),
			'moysklad_token' => $this->encryptSecret($data->getMoyskladToken()),
			'moysklad_role' => $data->getMoyskladRole(),
			'moysklad_tariff' => $data->getMoyskladTariff(),
			'moysklad_account_id' => $data->getMoyskladAccountId(),
		];

		if(false === wsklad()->database()->insert($this->getTableName(), $insert_data))
		{
			$object_id = new WP_Error('db_insert_error', __('Account could not insert into the database.', 'wsklad'), wsklad()->database()->last_error);
		}
		else
		{
			$object_id = wsklad()->database()->insert_id;
		}

		if($object_id && !is_wp_error($object_id))
		{
			$data->setId($object_id);

			$data->saveMetaData();
			$data->applyChanges();

			// hook
			do_action('wsklad_data_storage_account_create', $object_id, $data);
		}
	}

	/**
	 * Method to read an object from the database
	 *
	 * @param Account $data Data object
	 *
	 * @throws Exception If invalid Account
	 */
	public function read(&$data)
	{
		$data->setDefaults();

		if(!$data->getId())
		{
			throw new Exception('Invalid account.');
		}

		$table_name = $this->getTableName();

		$object_data = wsklad()->database()->get_row(wsklad()->database()->prepare("SELECT * FROM $table_name WHERE account_id = %d LIMIT 1", $data->getId()));

		if(!is_null($object_data))
		{
			$data->setProps
			(
				[
					'user_id' => $object_data->user_id,
					'connection_type'=> $object_data->connection_type,
					'name'=> $object_data->name,
					'status'=> $object_data->status ?: 'draft',
					'options' => self::readOptions($object_data->options),

					/**
					 * Dates are stored as VARCHAR and read through a strict parser.
					 *
					 * The previous guard was `0 < $object_data->date_create`, which relies
					 * on PHP comparing a non-numeric string to an int as strings. For
					 * `'2026-01-01'` that happens to work; for `'not-a-date'` it also
					 * happens to work — by accident, and for a different reason. A guard
					 * that is correct by coincidence is a guard that will stop being
					 * correct.
					 *
					 * `utilityStringToTimestampOrNull()` returns null for both an empty
					 * column and an unreadable one, and logs the second. That distinction
					 * is the whole point: "never set" and "set to something broken" are
					 * different facts and the operator needs to see the second one.
					 */
					'date_create' => $this->utilityStringToTimestampOrNull($object_data->date_create),
					'date_modify' => $this->utilityStringToTimestampOrNull($object_data->date_modify),
					'date_activity' => $this->utilityStringToTimestampOrNull($object_data->date_activity),
					'moysklad_login' => $object_data->moysklad_login,
					'moysklad_password' => $object_data->moysklad_password,
					'moysklad_token' => $object_data->moysklad_token,
					'moysklad_role' => $object_data->moysklad_role,
					'moysklad_tariff' => $object_data->moysklad_tariff,
					'moysklad_account_id' => $object_data->moysklad_account_id,
				]
			);
		}

		$this->readExtraData($data);
		$data->setObjectRead(true);

		do_action('wsklad_data_storage_account_read', $data->getId());
	}

	/**
	 * Method to update a data in the database
	 *
	 * @param Account $data Data object
	 */
	public function update(&$data)
	{
		$data->saveMetaData();

		$changes = $data->getChanges();

		// Only changed update data changes
		if
		(
			array_intersect
			(
				[
					'user_id',
					'connection_type',
					'name',
					'status',
					'options',
					'date_create',
					'date_modify',
					'date_activity',
					'moysklad_login',
					'moysklad_password',
					'moysklad_token',
					'moysklad_role',
					'moysklad_tariff',
					'moysklad_account_id',
				],
				array_keys($changes)
			)
		)
		{
			$update_data =
			[
				'user_id' => $data->getUserId(),
				'name' => $data->getName(),
				'status' => $data->getStatus(),
				'options' => self::writeOptions($data->getOptions()),
				'connection_type' => $data->getConnectionType(),
				'moysklad_login' => $data->getMoyskladLogin(),
				'moysklad_password' => $this->encryptSecret($data->getMoyskladPassword()),
				'moysklad_token' => $this->encryptSecret($data->getMoyskladToken()),
				'moysklad_role' => $data->getMoyskladRole(),
				'moysklad_tariff' => $data->getMoyskladTariff(),
				'moysklad_account_id' => $data->getMoyskladAccountId(),
			];

			if($data->getDateCreate('edit'))
			{
				$update_data['date_create'] = gmdate('Y-m-d H:i:s', $data->getDateCreate('edit')->getTimestamp());
			}

			if(isset($changes['date_modify']) && $data->getDateModify('edit'))
			{
				$update_data['date_modify'] = gmdate('Y-m-d H:i:s', $data->getDateModify('edit')->getTimestamp());
			}

			if(isset($changes['date_activity']) && $data->getDateActivity('edit'))
			{
				$update_data['date_activity'] = gmdate('Y-m-d H:i:s', $data->getDateActivity('edit')->getTimestamp());
			}

			wsklad()->database()->update($this->getTableName(), $update_data, ['account_id' => $data->getId()]);

			$data->readMetaData();
		}

		$data->applyChanges();

		do_action('wsklad_data_storage_account_update', $data->getId(), $data);
	}

	/**
	 * Method to delete an object from the database
	 *
	 * @param Account $data Data object
	 * @param array $args Array of args to pass to the delete method
	 */
	public function delete(&$data, array $args = []): bool
	{
		$object_id = $data->getId();

		if(!$object_id)
		{
			return false;
		}

		$args = wp_parse_args
		(
			$args,
			[
				'force_delete' => false
			]
		);

		if($args['force_delete'])
		{
			do_action('wsklad_data_storage_account_before_delete', $object_id);

			wsklad()->database()->delete($this->getTableName(), ['account_id' => $data->getId()]);

			/**
			 * Remove the account's meta rows with it.
			 *
			 * ⚠ These used to survive. The account row went, the meta rows stayed, and
			 * nothing reported it: no error, no log line, and the orphan is invisible
			 * until someone reads the meta table directly. Deleting an account and
			 * recreating one with the same id would then resurrect the old metadata.
			 *
			 * The delete is scoped to this account id and fires after the account row is
			 * gone, so it cannot run for a row that was only trashed.
			 */
			$meta_table = $this->getMetaTableName();

			if($meta_table)
			{
				$deleted_meta = wsklad()->database()->delete($meta_table, ['account_id' => $object_id]);

				if(false === $deleted_meta)
				{
					wsklad()->log()->error
					(
						'Account was deleted but its meta rows could not be removed.',
						['account_id' => $object_id, 'meta_table' => $meta_table]
					);
				}
			}

			/**
			 * There is no credentials table at this version.
			 *
			 * `wsklad_account_credentials` arrives with schema v5, and this release installs
			 * schema v3. The cleanup that used to live here asked
			 * `Schema::getCredentialsTable()` unconditionally and then checked whether the
			 * table existed — which reads as defensive and is not: the call itself raises an
			 * Error on a schema that never had the method, so the guard was never reached and
			 * permanently deleting an account killed the request.
			 *
			 * The secret lives in the account row at this version, so the row is the secret.
			 * Nothing to clean up beyond it.
			 */

			$data->setId(0);

			do_action('wsklad_data_storage_account_after_delete', $object_id);
		}
		else
		{
			do_action('wsklad_data_storage_account_before_trash', $object_id);

			$data->setStatus('deleted');
			$data->save();

			do_action('wsklad_data_storage_account_after_trash', $object_id);
		}

		return true;
	}

	/**
	 * Check if id is found for any other objects IDs
	 *
	 * @param int $object_id ID
	 *
	 * @return bool
	 */
	public function isExistingById(int $object_id): bool
	{
		return (bool) wsklad()->database()->get_var
		(
			wsklad()->database()->prepare
			(
				"SELECT account_id FROM " . $this->getTableName() . " WHERE  account_id = %d LIMIT 1",
				$object_id
			)
		);
	}

	/**
	 * Check if objects by name is found
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public function isExistingByName(string $value): bool
	{
		return (bool) wsklad()->database()->get_var
		(
			wsklad()->database()->prepare
			(
				"SELECT account_id FROM " . $this->getTableName() . " WHERE status != 'deleted' AND name = %s LIMIT 1",
				wp_slash($value)
			)
		);
	}

	/**
	 * Read extra data associated with the object, like button text or code URL for external objects.
	 *
	 * ⚠ Since 0.10.0 this is a no-op.
	 *
	 * The previous body called `get_post_meta($data->getId(), …)`. `$data->getId()` is an
	 * AUTO_INCREMENT from `wp_wsklad_accounts`, an ID space completely independent of
	 * `wp_posts.ID`, so it read post meta belonging to an unrelated entity that happened
	 * to share the integer. It could also not have worked as intended: the keys it
	 * produced (`set_<key>`) do not match any setter on `Account`, which uses camelCase.
	 * A latent cross-namespace data read is worse than no feature, so it is gone rather
	 * than "fixed" with another guess at intent.
	 *
	 * @param Account $data Data object
	 */
	protected function readExtraData(&$data)
	{
		do_action('wsklad_data_storage_account_read_extra_data', $data);
	}

	/**
	 * Add new piece of meta
	 *
	 * @param DataAbstract $data Data object
	 * @param Meta $meta (containing ->key and ->value)
	 *
	 * @return int meta ID
	 */
	public function addMeta(&$data, Meta $meta): int
	{
		$meta_table = $this->getMetaTableName();

		if(!$meta_table)
		{
			return false;
		}

		if(!$meta->key || !is_numeric($data->getId()))
		{
			return false;
		}

		$meta_key = wp_unslash($meta->key);
		$meta_value = wp_unslash($meta->value);

		$_meta_value = $meta_value;
		$meta_value  = maybe_serialize($meta_value);

		/**
		 * Fires immediately before meta of a specific type is added.
		 *
		 * @param int $object_id Object ID.
		 * @param string $meta_key Meta key.
		 * @param mixed $meta_value Meta value.
		 */
		do_action('wsklad_data_storage_account_meta_add', $data->getId(), $meta_key, $_meta_value);

		$result = wsklad()->database()->insert
		(
			$meta_table,
			[
				'account_id' => $data->getId(),
				'name' => $meta_key,
				'value' => $meta_value
			]
		);

		if(!$result)
		{
			return false;
		}

		$meta_id = (int) wsklad()->database()->insert_id;

		/**
		 * Fires immediately after meta of a specific type is added
		 *
		 * @param int $meta_id The meta ID after successful update.
		 * @param int $object_id Object ID.
		 * @param string $meta_key Meta key.
		 * @param mixed $meta_value Meta value.
		 */
		do_action('wsklad_data_storage_account_meta_added', $meta_id, $data->getId(), $meta_key, $_meta_value);

		return $meta_id;
	}

	/**
	 * Deletes meta based on meta ID
	 *
	 * @param DataAbstract $data Data object
	 * @param Meta $meta (containing at least -> id).
	 *
	 * @return mixed
	 */
	public function deleteMeta(&$data, Meta $meta)
	{
		$meta_table = $this->getMetaTableName();

		if(!$meta_table)
		{
			return false;
		}

		if(!$meta->key || !is_numeric($data->getId()))
		{
			return false;
		}

		$meta_id = (int) $meta->id;
		if($meta_id <= 0)
		{
			return false;
		}

		if(!$this->getMetadataById($meta_id))
		{
			return false;
		}

		// hook
		do_action('wsklad_data_storage_account_meta_delete', [$meta_id, $data->getId(), $meta->key, $meta->value]);

		$result = (bool) wsklad()->database()->delete
		(
			$meta_table,
			['meta_id' => $meta_id]
		);

		// hook
		do_action('wsklad_data_storage_account_meta_deleted', [$meta_id, $data->getId(), $meta->key, $meta->value]);

		return $result;
	}

	/**
	 * Update meta
	 *
	 * @param DataAbstract $data Data object
	 * @param Meta $meta (containing ->id, ->key and ->value).
	 *
	 * @return bool
	 */
	public function updateMeta(&$data, Meta $meta): bool
	{
		$meta_table = $this->getMetaTableName();

		if(!$meta_table)
		{
			return false;
		}

		if(!$meta->key || !is_numeric($data->getId()))
		{
			return false;
		}

		$meta_id = (int) $meta->id;
		if($meta_id <= 0)
		{
			return false;
		}

		if($_meta = $this->getMetadataById($meta_id))
		{
			$meta_value = maybe_serialize($meta->value);

			$metadata =
			[
				'name'   => $meta->key,
				'value' => $meta_value
			];

			$where = [];
			$where['meta_id'] = $meta_id;

			// hook
			do_action('wsklad_data_storage_account_meta_update', $meta_id, $data->getId(), $meta->key, $meta_value);

			$result = wsklad()->database()->update($meta_table, $metadata, $where, '%s', '%d');

			if(!$result)
			{
				return false;
			}

			// hook
			do_action('wsklad_data_storage_account_meta_updated', $meta->meta_id, $data->getId(), $meta->key, $meta_value);

			return true;
		}

		return false;
	}

	/**
	 * Get meta data by meta ID
	 *
	 * @param int $meta_id ID for a specific meta row
	 *
	 * @return object|false Meta object or false.
	 */
	public function getMetadataById(int $meta_id)
	{
		$meta_table = $this->getMetaTableName();

		if(!$meta_table)
		{
			return false;
		}

		$meta_id = (int) $meta_id;
		if($meta_id <= 0)
		{
			return false;
		}

		$meta = wsklad()->database()->get_row(wsklad()->database()->prepare("SELECT * FROM $meta_table WHERE meta_id = %d", $meta_id));

		if(empty($meta))
		{
			return false;
		}

		if(isset($meta->value))
		{
			// Same reasoning as readOptions(): a bare maybe_unserialize() would let a
			// crafted meta value instantiate objects. Scalars and arrays are all the
			// meta store is meant to hold.
			$decoded = is_string($meta->value) ? @unserialize($meta->value, ['allowed_classes' => false]) : $meta->value;

			$meta->value = false === $decoded && 'b:0;' !== $meta->value ? $meta->value : $decoded;
		}

		return $meta;
	}

	/**
	 * Returns an array of meta for an object.
	 *
	 * @param DataAbstract $data Data object
	 *
	 * @return array
	 */
	public function readMeta(&$data): array
	{
		$meta_table = $this->getMetaTableName();

		$raw_meta_data = wsklad()->database()->get_results
		(
			wsklad()->database()->prepare
			(
				"SELECT meta_id, name, value
				FROM {$meta_table}
				WHERE account_id = %d
				ORDER BY meta_id",
				$data->getId()
			)
		);

		//$this->internal_meta_keys = array_merge(array_map(array($this, 'prefix_key'), $object->get_data_keys()), $this->internal_meta_keys);

		//$meta_data = array_filter($raw_meta_data, array($this, 'exclude_internal_meta_keys'));

		return apply_filters('wsklad_data_storage_account_meta_read', $raw_meta_data, $data, $this);
	}

	/**
	 * Retrieves the total count of table entries
	 *
	 * @return int
	 */
	public function count(): int
	{
		$count = wsklad()->database()->get_var('SELECT COUNT(*) FROM ' . $this->getTableName() . ';');

		return (int) $count;
	}

	/**
	 * Retrieves the total count of table entries, filtered by the query parameter
	 *
	 * @param array $query
	 *
	 * @return int
	 */
	public function countBy(array $query)
	{
		if(!$query || !is_array($query) || count($query) <= 0)
		{
			return false;
		}

		$join = '';
		$where = '';

		if(isset($query['meta_query']))
		{
			$meta_query = new MetaQuery();
			$meta_query->parse_query_vars($query);

			$clauses = $meta_query->get_sql('account', $this->getTableName(), 'account_id');

			$join   .= $clauses['join'];
			$where  .= $clauses['where'];

			unset($query['meta_query']);
		}

		$sql_query = 'SELECT COUNT(*) FROM ' . $this->getTableName() . $join . ' WHERE 1=1 ';
		$sql_query .= $this->parseQueryConditions($query);
		$sql_query .= $where . ';';

		$count = wsklad()->database()->get_var($sql_query);

		return (int) $count;
	}

	/**
	 * Retrieve row counts per status in a single query.
	 *
	 * The accounts list used to call countBy() once per status, so six statuses meant
	 * six round trips before the page even started rendering its rows. One GROUP BY
	 * replaces them.
	 *
	 * @return array status => count, always containing every known status
	 */
	public function countByStatus(): array
	{
		// The statuses filter returns a list, not a map, so array_keys() would yield
		// [0,1,2,…] and seed the result with integer keys instead of status names.
		$statuses = array_values($this->utilityAccountsGetStatuses());

		$sql = 'SELECT status, COUNT(*) AS total FROM ' . $this->getTableName() . ' WHERE 1=1 GROUP BY status;';

		$rows = wsklad()->database()->get_results($sql, ARRAY_A);

		$counts = [];

		foreach($statuses as $status)
		{
			$counts[$status] = 0;
		}

		foreach((array) $rows as $row)
		{
			$status = is_array($row) ? $row['status'] : $row->status;
			$total  = is_array($row) ? $row['total'] : $row->total;

			$status = (string) $status;

			$counts[$status] = isset($counts[$status]) ? $counts[$status] + (int) $total : (int) $total;
		}

		return $counts;
	}

	/**
	 * Returns an array of data
	 *
	 * @param array $args Args
	 * @param string $type
	 *
	 * @return array|false|object
	 */
	public function getData(array $args = [], $type = OBJECT)
	{
		if(!$args || !is_array($args) || count($args) <= 0)
		{
			return false;
		}

		$join = '';
		$where = '';
		$limit = ' LIMIT 10';
		$offset = '';
		$orderby = '';
		$order = 'asc';

		if(isset($args['orderby']))
		{
			if(!isset($args['order']))
			{
				$args['order'] = $order;
			}

			$orderby = ' ORDER BY ' . $this->sanitizeIdentifier($args['orderby'], $this->getSortableColumns(), 'account_id')
				. ' ' . $this->sanitizeOrder($args['order']);
			unset($args['orderby'], $args['order']);
		}

		if(isset($args['offset']))
		{
			$offset = ' OFFSET ' . absint($args['offset']);
			unset($args['offset']);
		}
		if(isset($args['limit']))
		{
			$limit = ' LIMIT ' . absint($args['limit']);
			unset($args['limit']);
		}

		$fields = wsklad()->database()->base_prefix . 'wsklad_accounts.*';

		if(isset($args['fields']) && is_array($args['fields']))
		{
			$raw_field = [];

			foreach($args['fields'] as $field_key => $field)
			{
				if(is_array($field))
				{
					$raw_field[] = wsklad()->database()->base_prefix
						. $this->sanitizeIdentifier($field['name'], $this->getQueryableColumns())
						. ' as ' . $this->sanitizeIdentifier($field['alias'], $this->getQueryableColumns());
					continue;
				}

				$raw_field[] = wsklad()->database()->base_prefix
					. $this->sanitizeIdentifier($field, $this->getQueryableColumns());
			}

			$fields = implode(', ', $raw_field);

			unset($args['fields']);
		}

		if(isset($args['meta_query']))
		{
			$meta_query = new MetaQuery();
			$meta_query->parse_query_vars($args);

			$clauses = $meta_query->get_sql('account', $this->getTableName(), 'account_id');

			$join .= $clauses['join'];
			$where .= $clauses['where'];

			unset($args['meta_query']);
		}

		$sql_query = 'SELECT ' . $fields . ' FROM ' . $this->getTableName() . $join . ' WHERE 1=1 ';

		$sql_query .= $this->parseQueryConditions($args);

		$sql_query .= $where . $orderby . $limit . $offset . ';';

		$data = wsklad()->database()->get_results($sql_query, $type);

		if(!$data)
		{
			return false;
		}

		return $data;
	}

	/**
	 * Reduce an arbitrary value to a known column name.
	 *
	 * SQL identifiers cannot be bound as parameters, so the only safe handling is a
	 * whitelist. `sanitize_text_field()` is not one: it strips tags and encodes
	 * `<>&`, but happily passes commas, parentheses and spaces straight through.
	 *
	 * @param mixed $value
	 * @param array $allowed
	 * @param string $default
	 *
	 * @return string
	 */
	private function sanitizeIdentifier($value, array $allowed, string $default = ''): string
	{
		$value = is_string($value) ? trim($value) : '';

		return in_array($value, $allowed, true) ? $value : $default;
	}

	/**
	 * @param mixed $value
	 *
	 * @return string
	 */
	private function sanitizeOrder($value): string
	{
		$value = is_string($value) ? strtolower(trim($value)) : '';

		return in_array($value, ['asc', 'desc'], true) ? strtoupper($value) : 'ASC';
	}

	/**
	 * Build the WHERE fragment for a filter array.
	 *
	 * ⚠ Since 0.10.0 every branch is prepared and every column name is whitelisted.
	 * The previous string branch interpolated `"AND {$column_name} = '{$value}'"`
	 * with no escaping, and the column name was never checked in any branch — so a
	 * caller with an array key like `") UNION SELECT …"` injected through an argument
	 * that had no sanitising branch at all.
	 *
	 * @param array $query
	 *
	 * @return string
	 *
	 * @throws Exception When a column is not in the whitelist
	 */
	private function parseQueryConditions(array $query): string
	{
		$result = '';
		$allowed = $this->getQueryableColumns();

		foreach($query as $column_name => $value)
		{
			$column = $this->sanitizeIdentifier($column_name, $allowed);

			if('' === $column)
			{
				throw new Exception
				(
					sprintf
					(
						/* translators: %s: column name */
						__('Unknown column in accounts query: %s', 'wsklad'),
						is_string($column_name) ? $column_name : gettype($column_name)
					)
				);
			}

			if(is_array($value))
			{
				if(isset($value['compare_key']) && $value['compare_key'] === 'LIKE')
				{
					$like = wsklad()->database()->esc_like(wp_unslash($value['value']));

					$result .= wsklad()->database()->prepare("AND {$column} LIKE %s", '%' . $like . '%') . ' ';
				}
				else
				{
					$values_in = [];

					foreach($value as $item)
					{
						$values_in[] = absint($item);
					}

					if(empty($values_in))
					{
						// An empty IN() is a syntax error in MySQL; `IN (0)` matches nothing.
						$result .= "AND {$column} IN (0) ";
						continue;
					}

					$placeholders = implode(', ', array_fill(0, count($values_in), '%d'));

					$result .= wsklad()->database()->prepare("AND {$column} IN ({$placeholders})", $values_in) . ' ';
				}
			}
			elseif(is_string($value))
			{
				$result .= wsklad()->database()->prepare("AND {$column} = %s", $value) . ' ';
			}
			elseif(is_numeric($value))
			{
				$result .= wsklad()->database()->prepare("AND {$column} = %d", absint($value)) . ' ';
			}
			elseif($value === null)
			{
				$result .= "AND {$column} IS NULL ";
			}
			elseif(is_bool($value))
			{
				$result .= "AND {$column} = " . ($value ? 1 : 0) . ' ';
			}
		}

		return $result;
	}
}