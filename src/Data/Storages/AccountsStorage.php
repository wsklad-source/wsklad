<?php namespace Wsklad\Data\Storages;

defined('ABSPATH') || exit;

use WP_Error;
use Digiom\Woplucore\Data\Abstracts\WithMetaDataStorageAbstract;
use Digiom\Woplucore\Data\Meta;
use Wsklad\Data\Abstracts\DataAbstract;
use Wsklad\Data\Entities\Account;
use Wsklad\Data\MetaQuery;
use Wsklad\Exceptions\Exception;

/**
 * AccountsStorage
 *
 * @package Wsklad\Data\Storages
 */
class AccountsStorage extends WithMetaDataStorageAbstract
{
	/**
	 * @return string
	 */
	public function getTableName(): string
	{
		return wsklad()->database()->base_prefix . 'wsklad_accounts';
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
			'options' => maybe_serialize($data->getOptions()),
			'date_create' => gmdate('Y-m-d H:i:s', $data->getDateCreate('edit')->getTimestamp()),
			'date_modify' => $data->getDateModify(),
			'date_activity' => $data->getDateActivity(),
			'moysklad_login' => $data->getMoyskladLogin(),
			'moysklad_password' => $data->getMoyskladPassword(),
			'moysklad_token' => $data->getMoyskladToken(),
			'moysklad_role' => $data->getMoyskladRole(),
			'moysklad_tariff' => $data->getMoyskladTariff(),
			'moysklad_account_id' => $data->getMoyskladAccountId(),
		];

		if(false === wsklad()->database()->insert($this->getTableName(), $insert_data))
		{
			$object_id = new WP_Error('db_insert_error', __('Could not insert into the database'), wsklad()->database()->last_error);
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
					'options' => maybe_unserialize($object_data->options) ?: [],
					'date_create' => 0 < $object_data->date_create ? $this->utilityStringToTimestamp($object_data->date_create) : null,
					'date_modify' => 0 < $object_data->date_modify ? $this->utilityStringToTimestamp($object_data->date_modify) : null,
					'date_activity' => 0 < $object_data->date_activity ? $this->utilityStringToTimestamp($object_data->date_activity) : null,
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
				'options' => maybe_serialize($data->getOptions()),
				'connection_type' => $data->getConnectionType(),
				'moysklad_login' => $data->getMoyskladLogin(),
				'moysklad_password' => $data->getMoyskladPassword(),
				'moysklad_token' => $data->getMoyskladToken(),
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
	 * @param Account $data Data object
	 */
	protected function readExtraData(&$data)
	{
		foreach($data->getExtraDataKeys() as $extra_data_key)
		{
			$function = 'set_' . $extra_data_key;
			if(is_callable([$data, $function]))
			{
				$data->{$function}(
					get_post_meta($data->getId(), '_' . $extra_data_key, true) // todo get_post_meta
				);
			}
		}
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
	public function deleteMeta(&$data, Meta $meta): array
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
			$meta->value = maybe_unserialize($meta->value);
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

			$orderby = ' ORDER BY ' . $args['orderby'] . ' ' . $args['order'];
			unset($args['orderby'], $args['order']);
		}

		if(isset($args['offset']))
		{
			$offset = ' OFFSET ' . $args['offset'];
			unset($args['offset']);
		}
		if(isset($args['limit']))
		{
			$limit = ' LIMIT ' . $args['limit'];
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
					$raw_field[] = wsklad()->database()->base_prefix . $field['name'] . ' as ' . $field['alias'];
					continue;
				}

				$raw_field[] = wsklad()->database()->base_prefix . $field;
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
	 * @param array $query
	 *
	 * @return string
	 */
	private function parseQueryConditions(array $query): string
	{
		$result = '';

		foreach($query as $column_name => $value)
		{
			if(is_array($value))
			{
				if(isset($value['compare_key']) && $value['compare_key'] === 'LIKE')
				{
					$result .= "AND {$column_name} LIKE '%" . esc_sql(wsklad()->database()->esc_like(wp_unslash($value['value']))) . "%' ";
				}
				else
				{
					$valuesIn = implode(', ', array_map('absint', $value));
					$result   .= "AND {$column_name} IN ({$valuesIn}) ";
				}
			}
			elseif(is_string($value))
			{
				$result .= "AND {$column_name} = '{$value}' ";
			}
			elseif(is_numeric($value))
			{
				$value  = absint($value);
				$result .= "AND {$column_name} = {$value} ";
			}
			elseif($value === null)
			{
				$result .= "AND {$column_name} IS NULL ";
			}
		}

		return $result;
	}
}