<?php
/**
 * Namespace
 */
namespace Wsklad;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Exception;
use Wsklad\Api\MoySklad\ApiClient;
use Wsklad\Data\Storage;
use Wsklad\Data\Entities\DataAccounts;

/**
 * Class Account
 *
 * @package Wsklad\Data
 */
class Account extends DataAccounts
{
	/**
	 * @var ApiClient
	 */
	protected $moysklad;

	/**
	 * Default data
	 *
	 * @var array
	 */
	protected $data =
	[
		'user_id' => 0,
		'connection_type' => 'login',
		'name' => '',
		'status' => 'draft',
		'options' => [],
		'date_create' => null,
		'date_modify' => null,
		'date_activity' => null,
		'moysklad_login' => '',
		'moysklad_password' => '',
		'moysklad_token' => '',
		'moysklad_role' => '',
		'moysklad_tariff' => '',
		'moysklad_account_id' => '',
	];

	/**
	 * Account constructor.
	 *
	 * @param int $account
	 *
	 * @throws Exception
	 */
	public function __construct($account = 0)
	{
		parent::__construct();

		if(is_numeric($account) && $account > 0)
		{
			$this->set_id($account);
		}
		elseif($account instanceof self)
		{
			$this->set_id(absint($account->get_id()));
		}
		else
		{
			$this->set_object_read(true);
		}

		$this->storage = Storage::load($this->object_type);

		if($this->get_id() > 0)
		{
			$this->storage->read($this);
		}
	}

	/**
	 * Queries for API MoySklad by current Account
	 *
	 * @return false|ApiClient
	 */
	public function moysklad()
	{
		if(is_null($this->moysklad))
		{
			try
			{
				$host = wsklad()->settings()->get('api_moysklad_host', 'online.moysklad.ru');
				$force_https = true;
				if(wsklad()->settings()->get('api_moysklad_force_https', 'yes') !== 'yes')
				{
					$force_https = false;
				}

				$credentials = [];
				if($this->get_connection_type() === 'token')
				{
					$credentials['token'] = $this->get_moysklad_token();
				}
				else
				{
					$credentials['login'] = $this->get_moysklad_login();
					$credentials['password'] = $this->get_moysklad_password();
				}

				$this->moysklad = new ApiClient($host, $force_https, $credentials);
			}
			catch(Exception $exception)
			{
				return false;
			}
		}

		return $this->moysklad;
	}

	/**
	 * Get user id
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function get_user_id($context = 'view')
	{
		return $this->get_prop('user_id', $context);
	}

	/**
	 * Set user id
	 *
	 * @param string $value user_id
	 */
	public function set_user_id($value)
	{
		$this->set_prop('user_id', $value);
	}

	/**
	 * Get connection type
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function get_connection_type($context = 'view')
	{
		return $this->get_prop('connection_type', $context);
	}

	/**
	 * Set connection type
	 *
	 * @param string $value Type - token ot login
	 */
	public function set_connection_type($value)
	{
		$this->set_prop('connection_type', $value);
	}

	/**
	 * Get name
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function get_name($context = 'view')
	{
		return $this->get_prop('name', $context);
	}

	/**
	 * Set name
	 *
	 * @param string $value name
	 */
	public function set_name($value)
	{
		$this->set_prop('name', $value);
	}

	/**
	 * Get status
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function get_status($context = 'view')
	{
		return $this->get_prop('status', $context);
	}

	/**
	 * Set status
	 *
	 * @param string $value status
	 */
	public function set_status($value)
	{
		$this->set_prop('status', $value);
	}

	/**
	 * Get options
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return array
	 */
	public function get_options($context = 'view')
	{
		return $this->get_prop('options', $context);
	}

	/**
	 * Set options
	 *
	 * @param array $value options
	 */
	public function set_options($value)
	{
		$this->set_prop('options', $value);
	}

	/**
	 * Get created date
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return Datetime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_create($context = 'view')
	{
		return $this->get_prop('date_create', $context);
	}

	/**
	 * Get modified date
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return Datetime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_modify($context = 'view')
	{
		return $this->get_prop('date_modify', $context);
	}

	/**
	 * Get activity date
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return Datetime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_activity($context = 'view')
	{
		return $this->get_prop('date_activity', $context);
	}

	/**
	 * Set created date
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime.
	 * If the DateTime string has no timezone or
	 * offset, WordPress site timezone will be assumed. Null if their is no date.
	 *
	 * @throws Exception
	 */
	public function set_date_create($date = null)
	{
		$this->set_date_prop('date_create', $date);
	}

	/**
	 * Set modified date
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime.
	 * If the DateTime string has no timezone or
	 * offset, WordPress site timezone will be assumed. Null if their is no date.
	 *
	 * @throws Exception
	 */
	public function set_date_modify($date = null)
	{
		$this->set_date_prop('date_modify', $date);
	}

	/**
	 * Set activity date
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime.
	 * If the DateTime string has no timezone or
	 * offset, WordPress site timezone will be assumed. Null if their is no date.
	 *
	 * @throws Exception
	 */
	public function set_date_activity($date = null)
	{
		$this->set_date_prop('date_activity', $date);
	}

	/**
	 * Get moysklad login
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function get_moysklad_login($context = 'view')
	{
		return $this->get_prop('moysklad_login', $context);
	}

	/**
	 * Set moysklad login
	 *
	 * @param string $value moysklad_login
	 */
	public function set_moysklad_login($value)
	{
		$this->set_prop('moysklad_login', $value);
	}

	/**
	 * Get moysklad_password
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function get_moysklad_password($context = 'view')
	{
		return $this->get_prop('moysklad_password', $context);
	}

	/**
	 * Set user id
	 *
	 * @param string $value user_id
	 */
	public function set_moysklad_password($value)
	{
		$this->set_prop('moysklad_password', $value);
	}

	/**
	 * Get moysklad_token
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function get_moysklad_token($context = 'view')
	{
		return $this->get_prop('moysklad_token', $context);
	}

	/**
	 * Set moysklad_token
	 *
	 * @param string $value moysklad_token
	 */
	public function set_moysklad_token($value)
	{
		$this->set_prop('moysklad_token', $value);
	}

	/**
	 * Get moysklad_role
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function get_moysklad_role($context = 'view')
	{
		return $this->get_prop('moysklad_role', $context);
	}

	/**
	 * Set moysklad_role
	 *
	 * @param string $value moysklad_role
	 */
	public function set_moysklad_role($value)
	{
		$this->set_prop('moysklad_role', $value);
	}

	/**
	 * Get moysklad_tariff
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function get_moysklad_tariff($context = 'view')
	{
		return $this->get_prop('moysklad_tariff', $context);
	}

	/**
	 * Set moysklad_tariff
	 *
	 * @param string $value moysklad_tariff
	 */
	public function set_moysklad_tariff($value)
	{
		$this->set_prop('moysklad_tariff', $value);
	}

	/**
	 * Get moysklad_account_id
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function get_moysklad_account_id($context = 'view')
	{
		return $this->get_prop('moysklad_account_id', $context);
	}

	/**
	 * Set moysklad_account_id
	 *
	 * @param string $value moysklad_account_id
	 */
	public function set_moysklad_account_id($value)
	{
		$this->set_prop('moysklad_account_id', $value);
	}

	/**
	 * Returns if configuration is active.
	 *
	 * @return bool True if validation passes.
	 */
	public function is_active()
	{
		return $this->is_status('active');
	}

	/**
	 * Returns if configuration is inactive.
	 *
	 * @return bool True if validation passes.
	 */
	public function is_inactive()
	{
		return $this->is_status('inactive');
	}

	/**
	 * Returns if configuration is status.
	 *
	 * @param string $status
	 *
	 * @return bool True if validation passes.
	 */
	public function is_status($status = 'active')
	{
		return $status === $this->get_status();
	}
}