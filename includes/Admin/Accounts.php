<?php
/**
 * Namespace
 */
namespace Wsklad\Admin;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Wsklad\Traits\Singleton;
use Wsklad\Admin\Accounts\Create;
use Wsklad\Admin\Accounts\Update;
use Wsklad\Admin\Accounts\Delete;
use Wsklad\Admin\Accounts\Verification;
use Wsklad\Admin\Accounts\Lists;

/**
 * Class Accounts
 *
 * @package Wsklad\Admin
 */
class Accounts
{
	use Singleton;

	/**
	 * Available actions
	 *
	 * @var array
	 */
	private $actions =
	[
		'lists',
		'create',
		'update',
		'delete',
		'verification'
	];

	/**
	 * Current action
	 *
	 * @var string
	 */
	private $current_action = 'lists';

	/**
	 * Accounts constructor.
	 */
	public function __construct()
	{
		$actions = apply_filters(WSKLAD_ADMIN_PREFIX . 'accounts_init_actions', $this->actions);

		$this->set_actions($actions);

		$current_action = $this->init_current_action();

		switch($current_action)
		{
			case 'create':
				Create::instance();
				break;
			case 'update':
				Update::instance();
				break;
			case 'delete':
				Delete::instance();
				break;
			case 'verification':
				Verification::instance();
				break;
			default:
				Lists::instance();
		}
	}

	/**
	 * Current action
	 *
	 * @return string
	 */
	public function init_current_action()
	{
		$do_action = wsklad_get_var($_GET['do_action'], 'lists');

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
	public function get_actions()
	{
		return apply_filters(WSKLAD_ADMIN_PREFIX . 'accounts_get_actions', $this->actions);
	}

	/**
	 * Set actions
	 *
	 * @param array $actions
	 */
	public function set_actions($actions)
	{
		// hook
		$actions = apply_filters(WSKLAD_ADMIN_PREFIX . 'accounts_set_actions', $actions);

		$this->actions = $actions;
	}

	/**
	 * Get current action
	 *
	 * @return string
	 */
	public function get_current_action()
	{
		return apply_filters(WSKLAD_ADMIN_PREFIX . 'accounts_get_current_action', $this->current_action);
	}

	/**
	 * Set current action
	 *
	 * @param string $current_action
	 */
	public function set_current_action($current_action)
	{
		// hook
		$current_action = apply_filters(WSKLAD_ADMIN_PREFIX . 'accounts_set_current_action', $current_action);

		$this->current_action = $current_action;
	}
}