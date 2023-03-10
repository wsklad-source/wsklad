<?php namespace Wsklad\Admin;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Wsklad\Admin\Accounts\Dashboard;
use Wsklad\Admin\Accounts\Delete;
use Wsklad\Admin\Accounts\All;
use Wsklad\Admin\Accounts\Verification;
use Wsklad\Data\Storage;
use Wsklad\Data\Storages\AccountsStorage;
use Wsklad\Traits\UtilityTrait;

/**
 * Class Accounts
 *
 * @package Wsklad\Admin
 */
class Accounts
{
	use SingletonTrait;
	use UtilityTrait;

	/**
	 * Available actions
	 *
	 * @var array
	 */
	private $actions =
	[
		'all',
		'dashboard',
		'delete',
		'verification'
	];

	/**
	 * Current action
	 *
	 * @var string
	 */
	private $current_action = 'all';

	/**
	 * Accounts constructor.
	 */
	public function __construct()
	{
		$actions = apply_filters('wsklad_admin_accounts_init_actions', $this->actions);

		$this->set_actions($actions);

		$current_action = $this->init_current_action();

		switch($current_action)
		{
			case 'dashboard':
				Dashboard::instance();
				break;
			case 'delete':
				Delete::instance();
				break;
			case 'verification':
				Verification::instance();
				break;
			default:
				/** @var AccountsStorage $accounts */
				$accounts = Storage::load('account');

				$total_items = $accounts->count();

				if($total_items === 1)
				{
					$storage_args['limit'] = 2;
					$data = $accounts->get_data($storage_args, ARRAY_A);

					if(isset($data[0]))
					{
						wp_safe_redirect($this->utilityAdminAccountsGetUrl('dashboard', $data[0]['account_id']));
					}
				}
				else
				{
					All::instance();
				}
		}
	}

	/**
	 * Current action
	 *
	 * @return string
	 */
	public function init_current_action(): string
	{
		$do_action = wsklad()->getVar($_GET['do_action'], 'all');

		if(in_array($do_action, $this->get_actions(), true))
		{
			$this->set_current_action($do_action);
		}

		return $this->get_current_action();
	}

	/**
	 * Get actions
	 *
	 * @return array
	 */
	public function get_actions(): array
	{
		return apply_filters('wsklad_admin_accounts_get_actions', $this->actions);
	}

	/**
	 * Set actions
	 *
	 * @param array $actions
	 */
	public function set_actions(array $actions)
	{
		// hook
		$actions = apply_filters('wsklad_admin_accounts_set_actions', $actions);

		$this->actions = $actions;
	}

	/**
	 * Get current action
	 *
	 * @return string
	 */
	public function get_current_action(): string
	{
		return apply_filters('wsklad_admin_accounts_get_current_action', $this->current_action);
	}

	/**
	 * Set current action
	 *
	 * @param string $current_action
	 */
	public function set_current_action(string $current_action)
	{
		// hook
		$current_action = apply_filters('wsklad_admin_accounts_set_current_action', $current_action);

		$this->current_action = $current_action;
	}
}