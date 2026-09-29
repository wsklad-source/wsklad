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
		/**
		 * ⚠ Changed from 'login' to 'token' in 0.10.0.
		 *
		 * From 01.12.2026 Moy Sklad weights a Basic Auth request at 4 rate-limit units
		 * against 1 for a token, so the ceiling falls from 45 to 11 requests per 3
		 * seconds. Defaulting new accounts to `login` put every new account in the
		 * slower mode by construction.
		 *
		 * This is a default for *new* entities only. Accounts already in the database
		 * keep whatever they were saved with, and keep working — that is the 0.x
		 * compatibility promise, and it is why the login mode is deprecated rather than
		 * removed.
		 */
		'connection_type' => 'token',
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
	 * Directory where this account's log files are written.
	 *
	 * ⚠ Since 0.10.0 this is *outside* `wp-content/uploads`, because uploads are served
	 * as static files and a `.htaccess` only protects Apache. `getUploadDirectory('logs')`
	 * still returns the old location and is kept for extensions that read it; see UPGRADE.md.
	 *
	 * @return string
	 */
	public function getLogsDirectory(): string
	{
		return wsklad()->environment()->get('wsklad_accounts_logs_directory') . DIRECTORY_SEPARATOR . $this->getId();
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
	 * The value is decrypted here, so callers keep seeing a plain password. A row
	 * written before 0.10.0 holds plain text and is returned untouched — the migration
	 * to the encrypted form happens on the next write, not on read.
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function getMoyskladPassword(string $context = 'view'): string
	{
		return $this->redactedSecret('moysklad_password', $context);
	}

	/**
	 * Set moysklad_password
	 *
	 * @param string $value moysklad_password
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
		return $this->redactedSecret('moysklad_token', $context);
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
	 * Moy Sklad API request object
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

		$host = wsklad()->settings()->get('api_moysklad_host', 'api.moysklad.ru');
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
		/**
		 * Login-and-password mode.
		 *
		 * ⚠ Deprecated as a *new* configuration: since 0.12.2026 Moy Sklad weights a
		 * Basic Auth request at 4 rate-limit units against 1 for a token, so this mode
		 * runs at a quarter of the throughput. It is still fully supported — existing
		 * accounts keep working, which is the 0.x promise — but new accounts default to
		 * a token. Converting an account in place arrives with the extension contract; at
		 * this version there is no such helper, and an account keeps using whatever mode
		 * it was created with.
		 */
		else
		{
			$credentials['login'] = $this->getMoyskladLogin();
			$credentials['password'] = $this->getMoyskladPassword();
		}

		$this->moysklad = new Client($host, $force_https, $credentials);

		return $this->moysklad;
	}

	/**
	 * Decrypt a stored secret if it carries the encryption envelope.
	 *
	 * Plain text is returned unchanged, which is what makes the 0.10.0 migration
	 * transparent: an account saved before the upgrade keeps working, and is
	 * re-encrypted the next time it is written.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public static function decryptSecret(string $value): string
	{
		if('' === $value || !function_exists('wsklad'))
		{
			return $value;
		}

		$cryptography = wsklad()->cryptography();

		if(!$cryptography->isAvailable() || !$cryptography->isEncrypted($value))
		{
			return $value;
		}

		$plain = $cryptography->decrypt($value);

		// A failed decryption returns an empty string. Returning the envelope instead
		// would be a leaked ciphertext in a password field; returning empty keeps the
		// UI honest — the credential is simply wrong and must be re-entered.
		return $plain;
	}

	/**
	 * Decrypt a stored credential and register the plaintext with the redactor.
	 *
	 * Key-name redaction catches `['password' => '…']`. It does not catch a secret that
	 * arrives under a name giving nothing away — inside a request body, an exception
	 * context, a stack trace. Registering the actual value closes that: once a
	 * credential has been materialised in this request, it cannot appear anywhere in the
	 * output, whatever key it ends up under.
	 *
	 * ⚠ The value registered here is the *decrypted* one, and that ordering is the whole
	 * point. The previous version registered the raw column, and the raw column is a
	 * `v1$…` envelope when encryption is on — which Redactor::addKnownValue() refuses by
	 * design, because an envelope is already opaque. So in the one configuration that
	 * matters (sodium available, credentials encrypted) the call registered nothing at
	 * all, and a credential logged under a harmless key name was written in the clear.
	 * The integration suite passed because it registered its secret by hand and therefore
	 * never exercised this path.
	 *
	 * @param string $prop
	 * @param string $context
	 *
	 * @return string
	 */
	private function redactedSecret(string $prop, string $context): string
	{
		$plain = self::decryptSecret($this->getProp($prop, $context));

		if(class_exists('\Wsklad\Security\Redactor'))
		{
			\Wsklad\Security\Redactor::addKnownSecret($plain);
		}

		return $plain;
	}
}