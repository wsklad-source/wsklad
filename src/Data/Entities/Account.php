<?php namespace Wsklad\Data\Entities;

defined('ABSPATH') || exit;

use Digiom\ApiMoySklad\Client;
use Digiom\ApiMoySklad\Utils\HttpRequestExecutor;
use Wsklad\Data\Abstracts\AccountsDataAbstract;
use Wsklad\Data\Abstracts\DataAbstract;
use Wsklad\Data\Storage;
use Wsklad\Datetime;
use Wsklad\Exceptions\Exception;

/**
 * Account
 *
 * @package Wsklad\Data
 */
class Account extends AccountsDataAbstract
{
	/**
	 * @var Client
	 */
	protected $moysklad;

	/**
	 * @var array Default data
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
	 * Object constructor.
	 *
	 * @param int|DataAbstract $data
	 *
	 * @throws Exception|\Exception
	 */
	public function __construct($data = 0)
	{
		parent::__construct();

		if(is_numeric($data) && $data > 0)
		{
			$this->setId($data);
		}
		elseif($data instanceof self)
		{
			$this->setId(absint($data->getId()));
		}
		else
		{
			$this->setObjectRead(true);
		}

		$this->storage = Storage::load($this->object_type);

		if($this->getId() > 0)
		{
			$this->storage->read($this);
		}
	}

	/**
	 * Get user id
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function getUserId(string $context = 'view'): string
	{
		return $this->getProp('user_id', $context);
	}

	/**
	 * Set user id
	 *
	 * @param string|int $value user_id
	 */
	public function setUserId($value)
	{
		$this->setProp('user_id', $value);
	}

	/**
	 * Get name
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function getName(string $context = 'view'): string
	{
		return $this->getProp('name', $context);
	}

	/**
	 * Set name
	 *
	 * @param string $value name
	 */
	public function setName(string $value)
	{
		$this->setProp('name', $value);
	}

	/**
	 * Get status
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function getStatus(string $context = 'view'): string
	{
		return $this->getProp('status', $context);
	}

	/**
	 * Set status
	 *
	 * @param string $value status
	 */
	public function setStatus(string $value)
	{
		$this->setProp('status', $value);
	}

	/**
	 * Get options
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return array
	 */
	public function getOptions(string $context = 'view'): array
	{
		return $this->getProp('options', $context);
	}

	/**
	 * Set options
	 *
	 * @param array $value options
	 */
	public function setOptions(array $value)
	{
		$this->setProp('options', $value);
	}

	/**
	 * Get created date
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return Datetime|NULL object if the date is set or null if there is no date.
	 */
	public function getDateCreate(string $context = 'view')
	{
		return $this->getProp('date_create', $context);
	}

	/**
	 * Get modified date
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return Datetime|NULL object if the date is set or null if there is no date.
	 */
	public function getDateModify(string $context = 'view')
	{
		return $this->getProp('date_modify', $context);
	}

	/**
	 * Get activity date
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return Datetime|NULL object if the date is set or null if there is no date.
	 */
	public function getDateActivity(string $context = 'view')
	{
		return $this->getProp('date_activity', $context);
	}

	/**
	 * Set created date
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime.
	 * If the DateTime string has no timezone or
	 * offset, WordPress site timezone will be assumed. Null if their is no date.
	 *
	 * @throws Exception|\Exception
	 */
	public function setDateCreate($date = null)
	{
		$this->setDateProp('date_create', $date);
	}

	/**
	 * Set modified date
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime.
	 * If the DateTime string has no timezone or
	 * offset, WordPress site timezone will be assumed. Null if their is no date.
	 *
	 * @throws \Digiom\Woplucore\Data\Exceptions\Exception
	 */
	public function setDateModify($date = null)
	{
		$this->setDateProp('date_modify', $date);
	}

	/**
	 * Set activity date
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime.
	 * If the DateTime string has no timezone or
	 * offset, WordPress site timezone will be assumed. Null if their is no date.
	 *
	 * @throws \Digiom\Woplucore\Data\Exceptions\Exception
	 */
	public function setDateActivity($date = null)
	{
		$this->setDateProp('date_activity', $date);
	}

	/**
	 * Returns if account is active.
	 *
	 * @return bool True if validation passes.
	 */
	public function isActive(): bool
	{
		return $this->isStatus('active');
	}

	/**
	 * Returns if account is inactive.
	 *
	 * @return bool True if validation passes.
	 */
	public function isInactive(): bool
	{
		return $this->isStatus('inactive');
	}

	/**
	 * Returns if account enabled or not enabled.
	 *
	 * @return bool True if passes.
	 */
	public function isEnabled(): bool
	{
		$enabled = true;

		if($this->isInactive() || $this->isDraft())
		{
			$enabled = false;
		}

		return apply_filters($this->getHookPrefix() . 'enabled', $enabled, $this);
	}

	/**
	 * Returns if account is draft.
	 *
	 * @return bool True if validation passes.
	 */
	public function isDraft(): bool
	{
		return $this->isStatus('draft');
	}

	/**
	 * Returns if account is status.
	 *
	 * @param string $status
	 *
	 * @return bool True if validation passes.
	 */
	public function isStatus(string $status = 'active'): bool
	{
		return $status === $this->getStatus();
	}

	/**
	 * Returns upload directory for account.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function getUploadDirectory(string $context = 'main'): string
	{
		$upload_directory = wsklad()->environment()->get('wsklad_accounts_directory') . '/' . $this->getId();

		if($context === 'logs')
		{
			$upload_directory .= '/logs';
		}

        if($context === 'files')
        {
            $upload_directory .= '/files';
        }

		return $upload_directory;
	}

	/**
	 * Get moysklad login
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function getMoyskladLogin(string $context = 'view'): string
	{
		return $this->getProp('moysklad_login', $context);
	}

	/**
	 * Set moysklad login
	 *
	 * @param string $value moysklad_login
	 */
	public function setMoyskladLogin(string $value)
	{
		$this->setProp('moysklad_login', $value);
	}

	/**
	 * Get moysklad_password
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function getMoyskladPassword(string $context = 'view'): string
	{
		return $this->getProp('moysklad_password', $context);
	}

	/**
	 * Set user id
	 *
	 * @param string $value user_id
	 */
	public function setMoyskladPassword(string $value)
	{
		$this->setProp('moysklad_password', $value);
	}

	/**
	 * Get moysklad_token
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function getMoyskladToken(string $context = 'view'): string
	{
		return $this->getProp('moysklad_token', $context);
	}

	/**
	 * Set moysklad_token
	 *
	 * @param string $value moysklad_token
	 */
	public function setMoyskladToken(string $value)
	{
		$this->setProp('moysklad_token', $value);
	}

	/**
	 * Get moysklad_role
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function getMoyskladRole(string $context = 'view'): string
	{
		return $this->getProp('moysklad_role', $context);
	}

	/**
	 * Set moysklad_role
	 *
	 * @param string $value moysklad_role
	 */
	public function setMoyskladRole(string $value)
	{
		$this->setProp('moysklad_role', $value);
	}

	/**
	 * Get moysklad_tariff
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function getMoyskladTariff(string $context = 'view'): string
	{
		return $this->getProp('moysklad_tariff', $context);
	}

	/**
	 * Set moysklad_tariff
	 *
	 * @param string $value moysklad_tariff
	 */
	public function setMoyskladTariff(string $value)
	{
		$this->setProp('moysklad_tariff', $value);
	}

	/**
	 * Get moysklad_account_id
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function getMoyskladAccountId(string $context = 'view'): string
	{
		return $this->getProp('moysklad_account_id', $context);
	}

	/**
	 * Set moysklad_account_id
	 *
	 * @param string $value moysklad_account_id
	 */
	public function setMoyskladAccountId(string $value)
	{
		$this->setProp('moysklad_account_id', $value);
	}

	/**
	 * Get connection type
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function getConnectionType(string $context = 'view'): string
	{
		return $this->getProp('connection_type', $context);
	}

	/**
	 * Set connection type
	 *
	 * @param string $value Type - token ot login
	 */
	public function setConnectionType(string $value)
	{
		$this->setProp('connection_type', $value);
	}

	/**
	 * Объект запросов к АПИ
	 *
	 * @param string $path
	 *
	 * @return HttpRequestExecutor
	 * @throws \Exception
	 * @since 0.2
	 */
	public function api(string $path): HttpRequestExecutor
	{
		return $this->moysklad()->api($path);
	}

	/**
	 * Queries for API MoySklad by current Account
	 *
	 * @return Client
	 * @throws \Exception
	 */
	public function moysklad(): Client
	{
		if(!is_null($this->moysklad))
		{
			return $this->moysklad;
		}

		$host = wsklad()->settings()->get('api_moysklad_host', 'online.moysklad.ru');
		$force_https = true;
		if(wsklad()->settings()->get('api_moysklad_force_https', 'yes') !== 'yes')
		{
			$force_https = false;
		}

		$credentials = [];
		if($this->getConnectionType() === 'token')
		{
			$credentials['token'] = $this->getMoyskladToken();
		}
		else // todo: получение токена с сохранением и запросом уже по токену?
		{
			$credentials['login'] = $this->getMoyskladLogin();
			$credentials['password'] = $this->getMoyskladPassword();
		}

		$this->moysklad = new Client($host, $force_https, $credentials);

		return $this->moysklad;
	}
}