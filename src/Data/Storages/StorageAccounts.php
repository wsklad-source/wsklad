<?php
/**
 * Namespace
 */
namespace Wsklad\Data\Storages;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */

use Exception;
use stdClass;
use WP_Error;
use Wsklad\Abstracts\DataAbstract;
use Wsklad\Account;
use Wsklad\Data\Interfaces\StorageMetaInterface;
use Wsklad\Data\MetaQuery;
use Wsklad\Traits\DatetimeUtilityTrait;

/**
 * Class StorageAccounts
 *
 * @package Wsklad\Data\Storages
 */
class StorageAccounts implements StorageMetaInterface
{
	use DatetimeUtilityTrait;

	/**
	 * Data stored in meta keys, but not considered "meta" for an object.
	 *
	 * @var array
	 */
	protected $internal_meta_keys = [];

	/**
	 * Meta data which should exist in the DB, even if empty
	 *
	 * @var array
	 */
	protected $must_exist_meta_keys = [];

	/**
	 * @return string
	 */
	public function get_table_name()
	{
		return wsklad()->database()->base_prefix . 'wsklad_accounts';
	}

	/**
	 * @return string
	 */
	public function get_meta_table_name()
	{
		return wsklad()->database()->base_prefix . $this->get_table_name() .'_meta';
	}

	/**
	 * Method to create a new object in the database
	 *
	 * @param Account $data Account object
	 *
	 * @throws Exception
	 */
	public function create(&$data)
	{
		if(!$data->get_date_create('edit'))
		{
			$data->set_date_create(time());
		}

		$insert_data =
		[
			'user_id' => $data->get_user_id() ?: get_current_user_id(),
			'connection_type' => $data->get_connection_type(),
			'name' => $data->get_name(),
			'status' => $data->get_status(),
			'options' => maybe_serialize($data->get_options()),
			'date_create' => gmdate('Y-m-d H:i:s', $data->get_date_create('edit')->getTimestamp()),
			'date_modify' => $data->get_date_modify(),
			'date_activity' => $data->get_date_activity(),
			'moysklad_login' => $data->get_moysklad_login(),
			'moysklad_password' => $data->get_moysklad_password(),
			'moysklad_token' => $data->get_moysklad_token(),
			'moysklad_role' => $data->get_moysklad_role(),
			'moysklad_tariff' => $data->get_moysklad_tariff(),
			'moysklad_account_id' => $data->get_moysklad_account_id(),
		];

		if(false === wsklad()->database()->insert($this->get_table_name(), $insert_data))
		{
			$object_id = new WP_Error('db_insert_error', __('Could not insert Account into the database'), wsklad()->database()->last_error);
		}
		else
		{
			$object_id = wsklad()->database()->insert_id;
		}

		if($object_id && !is_wp_error($object_id))
		{
			$data->set_id($object_id);

			$data->save_meta_data();
			$data->apply_changes();

			// hook
			do_action('wsklad_data_storage_account_create', $object_id, $data);
		}
	}

	/**
	 * Method to read a object from the database
	 *
	 * @param Account $data Account object
	 *
	 * @throws Exception If invalid account
	 */
	public function read(&$data)
	{
		$data->set_defaults();

		if(!$data->get_id())
		{
			throw new Exception('Invalid account');
		}

		$table_name = $this->get_table_name();

		$object_data = wsklad()->database()->get_row(wsklad()->database()->prepare("SELECT * FROM $table_name WHERE account_id = %d LIMIT 1", $data->get_id()));

		if(!is_null($object_data))
		{
			$data->set_props
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

		$this->read_extra_data($data);

		$data->set_object_read(true);

		do_action('wsklad_data_storage_account_read', $data->get_id());
	}

	/**
	 * Method to update a data in the database
	 *
	 * @param Account $data Account object
	 */
	public function update(&$data)
	{
		$data->save_meta_data();

		$changes = $data->get_changes();

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
				'user_id' => $data->get_user_id(),
				'connection_type' => $data->get_connection_type(),
				'name' => $data->get_name(),
				'status' => $data->get_status(),
				'options' => maybe_serialize($data->get_options()),
				'date_create' => $data->get_date_create(),
				'date_modify' => $data->get_date_modify(),
				'date_activity' => $data->get_date_activity(),
				'moysklad_login' => $data->get_moysklad_login(),
				'moysklad_password' => $data->get_moysklad_password(),
				'moysklad_token' => $data->get_moysklad_token(),
				'moysklad_role' => $data->get_moysklad_role(),
				'moysklad_tariff' => $data->get_moysklad_tariff(),
				'moysklad_account_id' => $data->get_moysklad_account_id(),
			];

			if($data->get_date_create('edit'))
			{
				$update_data['date_create'] = gmdate('Y-m-d H:i:s', $data->get_date_create('edit')->getTimestamp());
			}

			if(isset($changes['date_modify']) && $data->get_date_modify('edit'))
			{
				$update_data['date_modify'] = gmdate('Y-m-d H:i:s', $data->get_date_modify('edit')->getTimestamp());
			}
			else
			{
				$update_data['date_modify'] = current_time('mysql', 1);
			}

			if(isset($changes['date_activity']) && $data->get_date_modify('edit'))
			{
				$update_data['date_activity'] = gmdate('Y-m-d H:i:s', $data->get_date_modify('edit')->getTimestamp());
			}

			wsklad()->database()->update($this->get_table_name(), $update_data, ['account_id' => $data->get_id()]);

			$data->read_meta_data(); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		}

		$data->apply_changes();

		do_action('wsklad_data_storage_account_update', $data->get_id(), $data);
	}

	/**
	 * Method to delete a object from the database
	 *
	 * @param Account $data Account object
	 * @param array $args Array of args to pass to the delete method
	 */
	public function delete(&$data, $args = [])
	{
		$object_id = $data->get_id();

		if(!$object_id)
		{
			return;
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

			wsklad()->database()->delete($this->get_table_name(), ['account_id' => $data->get_id()]);

			$data->set_id(0);

			do_action('wsklad_data_storage_account_after_delete', $object_id);
		}
		else
		{
			do_action('wsklad_data_storage_account_before_trash', $object_id);

			$data->set_status('deleted');
			$data->save();

			do_action('wsklad_data_storage_account_after_trash', $object_id);
		}
	}

	/**
	 * Check if id is found for any other objects IDs
	 *
	 * @param int $object_id ID
	 *
	 * @return bool
	 */
	public function is_existing_by_id($object_id)
	{
		return (bool) wsklad()->database()->get_var
		(
			wsklad()->database()->prepare
			(
				"SELECT account_id FROM " . $this->get_table_name() . " WHERE  account_id = %d LIMIT 1",
				$object_id
			)
		);
	}

	/**
	 * Check if objects by login is found
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public function is_existing_by_login($value)
	{
		return (bool) wsklad()->database()->get_var
		(
			wsklad()->database()->prepare(
				"
				SELECT account_id
				FROM " . $this->get_table_name() . "
				WHERE
				status != 'deleted'
				AND moysklad_login = %s
				LIMIT 1
				",
				wp_slash($value)
			)
		);
	}

	/**
	 * Check if objects by token is found
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public function is_existing_by_token($value)
	{
		return (bool) wsklad()->database()->get_var
		(
			wsklad()->database()->prepare(
				"
				SELECT account_id
				FROM " . $this->get_table_name() . "
				WHERE
				status != 'deleted'
				AND moysklad_token = %s
				LIMIT 1
				",
				wp_slash($value)
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
	public function is_existing_by_name($value)
	{
		return (bool) wsklad()->database()->get_var
		(
			wsklad()->database()->prepare(
				"
				SELECT account_id
				FROM " . $this->get_table_name() . "
				WHERE
				status != 'deleted'
				AND name = %s
				LIMIT 1
				",
				wp_slash($value)
			)
		);
	}

	/**
	 * Read extra data associated with the object, like button text or code URL for external objects.
	 *
	 * @param Account $data Data object
	 */
	protected function read_extra_data(&$data)
	{
		foreach($data->get_extra_data_keys() as $extra_data_key)
		{
			$function = 'set_' . $extra_data_key;
			if(is_callable([$data, $function]))
			{
				$data->{$function}(
					get_post_meta($data->get_id(), '_' . $extra_data_key, true) // todo get_post_meta
				);
			}
		}
	}

	/**
	 * Return list of internal meta keys
	 *
	 * @return array
	 */
	public function get_internal_meta_keys()
	{
		return $this->internal_meta_keys;
	}

	/**
	 * Callback to remove unwanted meta data
	 *
	 * @param object $meta Meta object to check if it should be excluded or not
	 *
	 * @return bool
	 */
	protected function exclude_internal_meta_keys($meta)
	{
		return !in_array($meta->meta_key, $this->internal_meta_keys, true) && 0 !== stripos($meta->meta_key, 'wp_');
	}

	/**
	 * Add new piece of meta
	 *
	 * @param DataAbstract $object Data object
	 * @param stdClass $meta (containing ->key and ->value)
	 *
	 * @return int meta ID
	 */
	public function add_meta(&$object, $meta)
	{
		$meta_table = $this->get_meta_table_name();

		if(!$meta_table)
		{
			return false;
		}

		if(!$meta->key || !is_numeric($object->get_id()))
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
		do_action('wsklad_data_storage_account_meta_add', $object->get_id(), $meta_key, $_meta_value);

		$result = wsklad()->database()->insert
		(
			$meta_table,
			[
				'account_id' => $object->get_id(),
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
		do_action('wsklad_data_storage_account_meta_added', $meta_id, $object->get_id(), $meta_key, $_meta_value);

		return $meta_id;
	}

	/**
	 * Deletes meta based on meta ID
	 *
	 * @param DataAbstract $object Data object
	 * @param stdClass $meta (containing at least -> id).
	 *
	 * @return bool
	 */
	public function delete_meta(&$object, $meta)
	{
		$meta_table = $this->get_meta_table_name();

		if(!$meta_table)
		{
			return false;
		}

		if(!$meta->key || !is_numeric($object->get_id()))
		{
			return false;
		}

		$meta_id = (int) $meta->id;
		if($meta_id <= 0)
		{
			return false;
		}

		if(!$this->get_metadata_by_id($meta_id))
		{
			return false;
		}

		// hook
		do_action('wsklad_data_storage_account_meta_delete', [$meta_id, $object->get_id(), $meta->key, $meta->value]);

		$result = (bool) wsklad()->database()->delete
		(
			$meta_table,
			['meta_id' => $meta_id]
		);

		// hook
		do_action('wsklad_data_storage_account_meta_deleted', [$meta_id, $object->get_id(), $meta->key, $meta->value]);

		return $result;
	}

	/**
	 * Update meta
	 *
	 * @param DataAbstract $object Data object
	 * @param stdClass $meta (containing ->id, ->key and ->value).
	 *
	 * @return bool
	 */
	public function update_meta(&$object, $meta)
	{
		$meta_table = $this->get_meta_table_name();

		if(!$meta_table)
		{
			return false;
		}

		if(!$meta->key || !is_numeric($object->get_id()))
		{
			return false;
		}

		$meta_id = (int) $meta->id;
		if($meta_id <= 0)
		{
			return false;
		}

		if($_meta = $this->get_metadata_by_id($meta_id))
		{
			$meta_value = maybe_serialize($meta->value);

			$data =
			[
				'name'   => $meta->key,
				'value' => $meta_value
			];

			$where = [];
			$where['meta_id'] = $meta_id;

			// hook
			do_action('wsklad_data_storage_account_meta_update', $meta_id, $object->get_id(), $meta->key, $meta_value);

			$result = wsklad()->database()->update($meta_table, $data, $where, '%s', '%d');

			if(!$result)
			{
				return false;
			}

			// hook
			do_action('wsklad_data_storage_account_meta_updated', $meta->meta_id, $object->get_id(), $meta->key, $meta_value);

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
	public function get_metadata_by_id($meta_id)
	{
		$meta_table = $this->get_meta_table_name();

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
	 * @param DataAbstract $object Data object
	 *
	 * @return array
	 */
	public function read_meta(&$object)
	{
		$meta_table = $this->get_meta_table_name();

		$raw_meta_data = wsklad()->database()->get_results
		(
			wsklad()->database()->prepare
			(
				"SELECT meta_id, name, value
				FROM {$meta_table}
				WHERE account_id = %d
				ORDER BY meta_id",
				$object->get_id()
			)
		);

		//$this->internal_meta_keys = array_merge(array_map(array($this, 'prefix_key'), $object->get_data_keys()), $this->internal_meta_keys);

		//$meta_data = array_filter($raw_meta_data, array($this, 'exclude_internal_meta_keys'));

		return apply_filters('wsklad_data_storage_account_meta_read', $raw_meta_data, $object, $this);
	}

	/**
	 * Internal meta keys we don't want exposed as part of meta_data. This is in
	 * addition to all data props with _ prefix.
	 *
	 * @param string $key Prefix to be added to meta keys
	 *
	 * @return string
	 */
	protected function prefix_key($key)
	{
		return '_' === substr($key, 0, 1) ? $key : '_' . $key;
	}

	/**
	 * Retrieves the total count of table entries
	 *
	 * @return int
	 */
	public function count()
	{
		$count = wsklad()->database()->get_var('SELECT COUNT(*) FROM ' . $this->get_table_name() . ';');

		return (int) $count;
	}

	/**
	 * Retrieves the total count of table entries, filtered by the query parameter
	 *
	 * @param array $query
	 *
	 * @return int
	 */
	public function count_by($query)
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

			$clauses = $meta_query->get_sql('account', $this->get_table_name(), 'account_id');

			$join   .= $clauses['join'];
			$where  .= $clauses['where'];

			unset($query['meta_query']);
		}

		$sql_query = 'SELECT COUNT(*) FROM ' . $this->get_table_name() . $join . ' WHERE 1=1 ';
		$sql_query .= $this->parse_query_conditions($query);
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
	 * @return mixed
	 */
	public function get_data($args = [], $type = OBJECT)
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

			$clauses = $meta_query->get_sql('account', $this->get_table_name(), 'account_id');

			$join .= $clauses['join'];
			$where .= $clauses['where'];

			unset($args['meta_query']);
		}

		$sql_query = 'SELECT ' . $fields . ' FROM ' . $this->get_table_name() . $join . ' WHERE 1=1 ';

		$sql_query .= $this->parse_query_conditions($args);

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
	private function parse_query_conditions($query)
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