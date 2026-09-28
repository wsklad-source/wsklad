<?php namespace Wsklad\Admin\Accounts;

defined('ABSPATH') || exit;

use Exception;
use Wsklad\Abstracts\TableAbstract;
use Wsklad\Data\Storage;
use Wsklad\Data\Storages\AccountsStorage;
use Wsklad\Traits\AccountsUtilityTrait;
use Wsklad\Traits\DatetimeUtilityTrait;
use Wsklad\Traits\UtilityTrait;

/**
 * Class AllTable
 *
 * @package Wsklad\Admin\Accounts
 */
class AllTable extends TableAbstract
{
	use AccountsUtilityTrait;
	use DatetimeUtilityTrait;
	use UtilityTrait;

	/**
	 * Accounts storage
	 *
	 * @var AccountsStorage
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
		catch(\Throwable $e){}

		parent::__construct($params);
	}

	/**
	 * No items found text
	 */
	public function noItems()
	{
		wsklad()->views()->getView('accounts/empty.php');
	}

	/**
	 * Get a list of CSS classes for the WP_List_Table table tag
	 *
	 * @return array - list of CSS classes for the table tag
	 */
	protected function getTableClasses(): array
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
	 * @return string
	 */
	public function columnDefault($item, string $column_name): string
	{
		switch ($column_name)
		{
			case 'account_id':
				return esc_html($item['account_id']);
			case 'date_create':
			case 'date_activity':
			case 'date_modify':
				return $this->prettyColumnsDate($item, $column_name);
			default:
				/**
				 * An unknown column is a bug in getColumns(), not a reason to render the
				 * whole row — the array held DB columns, credentials and options, and it was
				 * printed straight into the table cell.
				 */
				wsklad()->log()->debug
				(
					'Unexpected column in accounts list.',
					[
						'column' => $column_name,
						'item' => $item
					]
				);

				return '';
		}
	}

	/**
	 * @param $item
	 * @param $column_name
	 *
	 * @return string
	 */
	private function prettyColumnsDate($item, $column_name): string
	{
		$date = $item[$column_name];
		$timestamp = $this->utilityStringToTimestamp($date) + $this->utilityTimezoneOffset();

		if(!empty($date))
		{
			return sprintf
			(
				'%s <br/><span class="time">%s: %s</span><br>(%s %s)',
				date_i18n('d/m/Y', $timestamp),
				__('Time', 'wsklad'),
				date_i18n('H:i:s', $timestamp),
				human_time_diff($timestamp, current_time('timestamp')),
                __('ago', 'wsklad')
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
	public function columnStatus($item): string
	{
		$status = $this->utilityAccountsGetStatusesLabel($item['status']);

		$status_class = '';
		$status_description = '';

		if($item['status'] === 'draft')
		{
			$status_class = 'draft';
			$status_description = __('An initial setup is required.', 'wsklad');
		}
		if($item['status'] === 'active')
		{
			$status_class = 'active';
			$status_description = __('All account algorithms are active.', 'wsklad');
		}
		if($item['status'] === 'inactive')
		{
			$status_class = 'inactive';
			$status_description = __('All account algorithms are disabled.', 'wsklad');
		}
		if($item['status'] === 'processing')
		{
			$status_class = 'processing';
			$status_description = __('Data is being exchanged. Changing settings is not recommended.', 'wsklad');
		}
		if($item['status'] === 'error')
		{
			$status_class = 'error';
			$status_description = __('An error has occurred. You need to look at the event logs, they contain detailed information.', 'wsklad');
		}
		if($item['status'] === 'deleted')
		{
			$status_class = 'deleted';
			$status_description = __('Awaiting final removal. All algorithms are disabled.', 'wsklad');
		}

		return '<span class="' . esc_attr( $status_class ) . '" data-bs-custom-class="accounts-status-popover ' . esc_attr( $status_class ) . '" data-bs-title="' . esc_attr__( 'Status description', 'wsklad' ) . '"  data-bs-toggle="popover" data-bs-trigger="hover focus click" data-bs-content="' . esc_attr( $status_description ) . '">' . esc_html( $status ) . '</span>';
	}

	/**
	 * Verification action URL.
	 *
	 * The link is a GET that triggers a real Moy Sklad request, so it carries a nonce:
	 * without one any off-site image tag was enough to make a logged-in administrator
	 * trigger the action. `wp_nonce_url()` escapes its own return value, so it is not
	 * wrapped in `esc_url()` here — that would encode the `&#038;` a second time.
	 *
	 * @param mixed $account_id
	 *
	 * @return string
	 */
	private function utilityVerificationUrl($account_id): string
	{
		return wp_nonce_url
		(
			$this->utilityAdminAccountsGetUrl('verification', $account_id),
			'wsklad_accounts_verify'
		);
	}

	/**
	 * The disconnect link is a GET that moves an account to the trash, so it carries a
	 * nonce for the same reason as the verification link. Without it, an off-site image
	 * tag was enough to trash a shop's accounts. `wp_nonce_url()` escapes its own
	 * return value, so it is not wrapped in `esc_url()`.
	 *
	 * @param mixed $account_id
	 *
	 * @return string
	 */
	private function utilityDeleteUrl($account_id): string
	{
		return wp_nonce_url
		(
			$this->utilityAdminAccountsGetUrl('delete', $account_id),
			'wsklad_accounts_delete'
		);
	}

	/**
	 * Account connection type
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_connection_type($item): string
	{
		$actions =
		[
			'dashboard' => '<a href="' . esc_url( $this->utilityAdminAccountsGetUrl('dashboard', $item['account_id']) ) . '">' . esc_html__( 'Open dashboard', 'wsklad' ) . '</a>',
			'verification' => '<a href="' . $this->utilityVerificationUrl( $item['account_id'] ) . '">' . esc_html__( 'Verification', 'wsklad' ) . '</a>',
			'delete' => '<a href="' . $this->utilityDeleteUrl( $item['account_id'] ) . '">' . esc_html__( 'Mark as deleted', 'wsklad' ) . '</a>',
		];

		if('deleted' === $item['status'] || ('draft' === $item['status'] && 'yes' === wsklad()->settings()->get('accounts_draft_delete', 'yes')))
		{
			unset($actions['verification']);
			$actions['delete'] = '<a href="' . $this->utilityDeleteUrl( $item['account_id'] ) . '">' . esc_html__( 'Remove forever', 'wsklad' ) . '</a>';
		}

		if('active' === $item['status'])
		{
			unset($actions['delete']);
		}

		$actions = apply_filters('wsklad_admin_accounts_all_row_actions', $actions, $item);

		$user = get_userdata($item['user_id']);
		if($user instanceof \WP_User && $user->exists())
		{
			$metas['user'] = esc_html__( 'User: ', 'wsklad' ) . esc_html( $user->get('nickname') ) . ' (' . esc_html( $item['user_id'] ) . ')';
		}
		else
		{
			$metas['user'] =  esc_html__( 'User is not exists.', 'wsklad');
		}

        if(has_filter('wsklad_admin_accounts_all_row_metas'))
        {
            $metas = apply_filters('wsklad_admin_accounts_all_row_metas', $metas, $item);
        }

		$metas['connection_type'] = esc_html__( 'Connection type: ', 'wsklad' ) . '<b>' . esc_html( $this->utilityAccountsGetTypesLabel($item['connection_type']) ) . '</b>';

		return sprintf( '<span class="account-name">%1$s</span><div class="account-metas">%2$s</div><div class="account-actions">%3$s</div>',
			esc_html($item['name']),
			$this->rowMetas($metas),
			$this->rowActions($actions, true)
		);
	}

	/**
	 * @param $data
	 *
	 * @return string
	 */
	public function rowMetas($data): string
	{
		$metas_count = count($data);

		if(!$metas_count)
		{
			return '';
		}

		$out = '<div class="row-metas">';

		foreach($data as $meta => $meta_text)
		{
			$out .= "<div class='row-metas-line " . esc_attr( $meta ) . "'>" . wp_kses_post( $meta_text ) . "</div>";
		}

		$out .= '</div>';

		return $out;
	}

	/**
	 * All columns
	 *
	 * @return array
	 */
	public function getColumns(): array
	{
		$columns = [];

		$columns['account_id'] = __('ID', 'wsklad');
		$columns['connection_type'] = __('Base information', 'wsklad');
		$columns['status'] = __('Status', 'wsklad');
		$columns['date_create'] = __('Connection date', 'wsklad');
		$columns['date_activity'] = __('Last activity', 'wsklad');

		return $columns;
	}

	/**
	 * Sortable columns
	 *
	 * @return array
	 */
	public function getSortableColumns(): array
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
	protected function getDefaultPrimaryColumnName(): string
	{
		return 'account_id';
	}

	/**
	 * Counter label for the views bar.
	 *
	 * The number is the only part that inflects, so it is the only part that goes
	 * through `_n()`: the status names come from a filterable map and cannot be a
	 * gettext literal.
	 *
	 * @param mixed $count
	 *
	 * @return string
	 */
	private function countLabel($count): string
	{
		$count = absint($count);

		return sprintf
		(
			/* translators: %s: number of accounts. */
			_n('%s item', '%s items', $count, 'wsklad'),
			$count
		);
	}

	/**
	 * Creates the different status filter links at the top of the table.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getViews(): array
	{
		$status_links = [];
		$current = !empty($_REQUEST['status']) ? sanitize_key(wp_unslash($_REQUEST['status'])) : 'all';

		$counts = $this->storage_accounts->countByStatus();

		// All link
		$class = $current === 'all' ? ' class="current"' :'';
		$all_url = esc_url(remove_query_arg('status'));

		$status_links['all'] = sprintf
		(
			'<a href="%s" %s>%s <span class="count">(%s)</span></a>',
			$all_url,
			$class,
			esc_html__('All', 'wsklad'),
			$this->countLabel($this->storage_accounts->count())
		);

		$statuses = $this->utilityAccountsGetStatuses();

		foreach($statuses as $status_key)
		{
			$count = isset($counts[$status_key]) ? (int) $counts[$status_key] : 0;

			if($count === 0)
			{
				continue;
			}

			$class = $current === $status_key ? ' class="current"' :'';
			$sold_url = esc_url(add_query_arg('status', $status_key));

			$status_links[$status_key] = sprintf
			(
				'<a href="%s" %s>%s <span class="count">(%s)</span></a>',
				$sold_url,
				$class,
				esc_html($this->utilityAccountsGetStatusesFolder($status_key)),
				$this->countLabel($count)
			);
		}

		return $status_links;
	}

	/**
	 * Build items
	 */
	public function prepareItems()
	{
		/**
		 * First, lets decide how many records per page to show
		 */
		$per_page = wsklad()->settings()->get('accounts_show_per_page', 10);

		/**
		 * REQUIRED. Now we need to define our column headers. This includes a complete
		 * array of columns to be displayed (slugs & titles), a list of columns
		 * to keep hidden, and a list of columns that are sortable. Each of these
		 * can be defined in another method (as we've done here) before being
		 * used to build the value for our _column_headers property.
		 */
		$columns = $this->getColumns();
		$hidden = [];
		$sortable = $this->getSortableColumns();

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
		$current_page = $this->getPagenum();

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

		/**
		 * `sanitize_text_field()` is not a whitelist: it strips tags and encodes `<>&`,
		 * but commas, parentheses and spaces survive, so a crafted `orderby` reached the
		 * ORDER BY clause. SQL identifiers cannot be bound as parameters, so the only
		 * safe handling is to reduce the value to a column the storage declares sortable.
		 */
		$orderby = 'account_id';
		$order = 'desc';

		if(!empty($_REQUEST['orderby']))
		{
			$requested_orderby = sanitize_text_field(wp_unslash($_REQUEST['orderby']));

			if(in_array($requested_orderby, $this->storage_accounts->getSortableColumns(), true))
			{
				$orderby = $requested_orderby;
			}
		}

		if(!empty($_REQUEST['order']))
		{
			$requested_order = strtolower(sanitize_text_field(wp_unslash($_REQUEST['order'])));

			if(in_array($requested_order, ['asc', 'desc'], true))
			{
				$order = $requested_order;
			}
		}

		$storage_args = [];

		if(!empty($_GET['status']))
		{
			$requested_status = sanitize_key(wp_unslash($_GET['status']));

			if(in_array($requested_status, $this->utilityAccountsGetStatuses(), true))
			{
				$storage_args['status'] = $requested_status;
			}
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
			$total_items = $this->storage_accounts->countBy($storage_args);
		}

		$storage_args['offset'] = $offset;
		$storage_args['limit'] = $per_page;
		$storage_args['orderby'] = $orderby;
		$storage_args['order'] = $order;

		$this->items = $this->storage_accounts->getData($storage_args, ARRAY_A);

		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$this->setPaginationArgs
		(
			[
				'total_items' => $total_items,
                'per_page'    => $per_page,
                'total_pages' => ceil($total_items / $per_page)
            ]
		);
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @param string $which
	 */
	protected function extraTablenav(string $which)
	{
		if('top' === $which)
		{
			$this->views();
		}
	}
}