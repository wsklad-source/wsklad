<?php
/**
 * Namespace
 */
namespace Wsklad\Admin\Accounts;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Exception;
use Wsklad\Abstracts\TableAbstract;
use Wsklad\Data\Storage;
use Wsklad\Data\Storages\StorageAccounts;
use Wsklad\Settings\ConnectionSettings;

/**
 * Class ListsTable
 *
 * @package Wsklad\Admin\Accounts
 */
class ListsTable extends TableAbstract
{
	/**
	 * Accounts storage
	 *
	 * @var StorageAccounts
	 */
	public $storage_accounts;

	/**
	 * ListsTable constructor.
	 */
	public function __construct()
	{
	    $params =
        [
            'singular' => 'account',
            'plural' => 'accounts',
            'ajax' => false
        ];

		try
		{
			$this->storage_accounts = Storage::load('account');
		}
		catch(Exception $e){}

		parent::__construct($params);
	}

	/**
	 * No items found text
	 */
	public function no_items()
	{
		wsklad_get_template('accounts/empty.php');
	}

	/**
	 * Get a list of CSS classes for the WP_List_Table table tag
	 *
	 * @return array - list of CSS classes for the table tag
	 */
	protected function get_table_classes()
	{
		return
        [
		    'widefat',
            'striped',
            $this->_args['plural']
        ];
	}

	/**
	 * Default print rows
	 *
	 * @param object $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default($item, $column_name)
	{
		switch ($column_name)
		{
			case 'account_id':
				return $item['account_id'];
			case 'date_create':
			case 'date_activity':
			case 'date_modify':
				return $this->pretty_columns_date($item, $column_name);
			default:
				return print_r($item, true);
		}
	}

	/**
	 * @param $item
	 * @param $column_name
	 *
	 * @return string
	 */
	private function pretty_columns_date($item, $column_name)
	{
		$date = $item[$column_name];
		$timestamp = wsklad_string_to_timestamp($date) + wsklad_timezone_offset();

		if(!empty($date))
		{
			return sprintf
			(
				__('%s <br/><span class="time">Time: %s</span>', 'wsklad'),
				date_i18n('d/m/Y', $timestamp),
				date_i18n('H:i:s', $timestamp)
			);
		}

		return __('No activity', 'wsklad');
	}

	/**
	 * Account status
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_status($item)
	{
		$status = wsklad_accounts_statuses_get_label($item['status']);
		$status_return = wsklad_accounts_statuses_get_label('error');

		if($item['status'] === 'draft')
		{
			$status_return = '<span class="draft">' . $status . '</span>';
		}
		if($item['status'] === 'active')
		{
			$status_return = '<span class="active">' . $status . '</span>';
		}
		if($item['status'] === 'inactive')
		{
			$status_return = '<span class="inactive">' . $status . '</span>';
		}
		if($item['status'] === 'processing')
		{
			$status_return = '<span class="processing">' . $status . '</span>';
		}
		if($item['status'] === 'error')
		{
			$status_return = '<span class="error">' . $status . '</span>';
		}
		if($item['status'] === 'deleted')
		{
			$status_return = '<span class="deleted">' . $status . '</span>';
		}

		return $status_return;
	}

	/**
	 * Account name
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_connection_type($item)
	{
		$actions =
		[
			'update' => '<a href="' . wsklad_admin_accounts_get_url('update', $item['account_id']) . '">' . __('Edit', 'wsklad') . '</a>',
			'delete' => '<a href="' . wsklad_admin_accounts_get_url('delete', $item['account_id']) . '">' . __('Delete', 'wsklad') . '</a>',
		];

		if('deleted' === $item['status'])
		{
			unset($actions['update'], $actions['delete']);
			$actions['delete'] = '<a href="' . wsklad_admin_accounts_get_url('delete', $item['account_id']) . '">' . __('Remove forever', 'wsklad') . '</a>';
		}

		$connection_type = __('Connection type: ', 'wsklad') . '<b>' . wsklad_accounts_connection_types_get_label($item['connection_type']) . '</b>';
		$connection_role = __('Moy Sklad role: ', 'wsklad') . '<b>' . wsklad_accounts_connection_types_get_label($item['moysklad_role']) . '</b>';
		$connection_tariff = __('Moy Sklad tariff: ', 'wsklad') . '<b>' . wsklad_accounts_connection_types_get_label($item['moysklad_tariff']) . '</b>';

		/**
		 * Вывод:
		 * типа подключения
		 * кнопок - удалить, если статус не удален
		 *          удалить окончательно, если статус удален
		 *          редактировать, с открытием окна редактирования
		 *          приостановить, со сменой статуса на не активен
		 *          возобновить, со сменой статуса на активен
		 * информации об аккаунте, например наименование, тип пользователя, тариф, срок подписки и т.п.
		 */
		return sprintf( '%1$s<br/>%2$s<br/>%3$s<br/>%4$s',
			/*$1%s*/
			$connection_type,
			$connection_role,
			$connection_tariff,
			/*$2%s*/
			$this->row_actions($actions, true)
		);
	}

	/**
	 * All columns
	 *
	 * @return array
	 */
	public function get_columns()
	{
		$columns = [];

		$columns['account_id'] = __('ID', 'wsklad');
		$columns['connection_type'] = __('Base information', 'wsklad');
		$columns['status'] = __('Status', 'wsklad');
		$columns['date_create'] = __('Create date', 'wsklad');
		$columns['date_activity'] = __('Last activity', 'wsklad');

		return $columns;
	}

	/**
	 * Sortable columns
	 *
	 * @return array
	 */
	public function get_sortable_columns()
	{
		$sortable_columns['account_id'] = ['account_id', false];
		$sortable_columns['status'] = ['status', false];

		return $sortable_columns;
	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @return string The name of the primary column
	 */
	protected function get_default_primary_column_name()
	{
		return 'account_id';
	}

	/**
	 * Creates the different status filter links at the top of the table.
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function get_views()
	{
		$status_links = [];
		$current = !empty($_REQUEST['status']) ? $_REQUEST['status'] : 'all';

		// All link
		$class = $current === 'all' ? ' class="current"' :'';
		$all_url = remove_query_arg('status');

		$status_links['all'] = sprintf
		(
			'<a href="%s" %s>%s <span class="count">(%d)</span></a>',
			$all_url,
			$class,
			__('All', 'wsklad'),
			$this->storage_accounts->count()
		);

		$statuses = wsklad_accounts_get_statuses();

		foreach($statuses as $status_key)
		{
			$count = $this->storage_accounts->count_by(
				[
					'status' => $status_key
				]
			);

			if($count === 0)
			{
				continue;
			}

			$class = $current === $status_key ? ' class="current"' :'';
			$sold_url = esc_url(add_query_arg('status', $status_key));

			$status_links[$status_key] = sprintf
			(
				'<a href="%s" %s>%s <span class="count">(%d)</span></a>',
				$sold_url,
				$class,
				wsklad_accounts_get_statuses_folder($status_key),
				$count
			);
		}

		return $status_links;
	}

	/**
	 * Build items
	 */
	public function prepare_items()
	{
		/**
		 * First, lets decide how many records per page to show
		 */
		$per_page = wsklad()->settings()->get('accounts_per_page_show', 10);

		/**
		 * REQUIRED. Now we need to define our column headers. This includes a complete
		 * array of columns to be displayed (slugs & titles), a list of columns
		 * to keep hidden, and a list of columns that are sortable. Each of these
		 * can be defined in another method (as we've done here) before being
		 * used to build the value for our _column_headers property.
		 */
		$columns = $this->get_columns();
		$hidden = [];
		$sortable = $this->get_sortable_columns();

		/**
		 * REQUIRED. Finally, we build an array to be used by the class for column
		 * headers. The $this->_column_headers property takes an array which contains
		 * 3 other arrays. One for all columns, one for hidden columns, and one
		 * for sortable columns.
		 */
		$this->_column_headers = [$columns, $hidden, $sortable];

		/**
		 * REQUIRED for pagination. Let's figure out what page the user is currently
		 * looking at. We'll need this later, so you should always include it in
		 * your own package classes.
		 */
		$current_page = $this->get_pagenum();

		/**
		 * Instead of querying a database, we're going to fetch the example data
		 * property we created for use in this plugin. This makes this example
		 * package slightly different than one you might build on your own. In
		 * this example, we'll be using array manipulation to sort and paginate
		 * our data. In a real-world implementation, you will probably want to
		 * use sort and pagination data to build a custom query instead, as you'll
		 * be able to use your precisely-queried data immediately.
		 */
		$offset = 0;

		if(1 < $current_page)
		{
			$offset = $per_page * ($current_page - 1);
		}

		$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'account_id';
		$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'desc';

		$storage_args = [];

		if(array_key_exists('status', $_GET) && in_array($_GET['status'], wsklad_accounts_get_statuses(), true))
		{
			$storage_args['status'] = $_GET['status'];
		}

		/**
		 * REQUIRED for pagination. Let's check how many items are in our data array.
		 * In real-world use, this would be the total number of items in your database,
		 * without filtering. We'll need this later, so you should always include it
		 * in your own package classes.
		 */
		if(empty($storage_args))
		{
			$total_items = $this->storage_accounts->count();
		}
		else
		{
			$total_items = $this->storage_accounts->count_by($storage_args);
		}

		$storage_args['offset'] = $offset;
		$storage_args['limit'] = $per_page;
		$storage_args['orderby'] = $orderby;
		$storage_args['order'] = $order;

		$this->items = $this->storage_accounts->get_data($storage_args, ARRAY_A);

		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args
		(
			[
				'total_items' => $total_items,
                'per_page'    => $per_page,
                'total_pages' => ceil($total_items / $per_page)
            ]
		);
	}

	/**
	 * Connect box
	 *
	 * @param string $text Button text
	 * @param false $status
	 */
	public function connect_box($text, $status = false)
	{
		$class = 'button';
		if($status === false)
		{
			$class .= ' button-primary';
		}
		else
		{
			$class .= ' button-green';
		}

		echo '<a href="' . admin_url('admin.php?page=wsklad&section=settings&do_settings=connection') . '" class="' . $class . '" style="float: right;"> ' . $text . ' </a>';
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @param string $which
	 */
	protected function extra_tablenav($which)
	{
		if('top' === $which)
		{
			$this->views();

			$connection_settings = new ConnectionSettings();

			if($connection_settings->isConnected())
			{
				$this->connect_box(__($connection_settings->get('login', 'Undefined'), 'wsklad' ), true);
			}
			else
			{
				$this->connect_box(__( 'Connection to the WSklad', 'wsklad'));
			}
		}
	}
}