<?php
/**
 * Namespace
 */
namespace Wsklad\MoySklad;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Exception;
use Digiom\Psr7wp\HttpClient;
use Wsklad\MoySklad\Clients\EntityClient;
use Wsklad\MoySklad\Utils\StringsTrait;

/**
 * Class ApiClient
 *
 * @package Wsklad\MoySklad
 */
class ApiClient
{
	use StringsTrait;

	/**
	 * @var string
	 */
	private $host;

	/**
	 * @var string
	 */
	private $login;

	/**
	 * @var string
	 */
	private $password;

	/**
	 * @var string
	 */
	private $token = '';

	/**
	 * @var HttpClient
	 */
	private $httpClient;

	/**
	 * @var bool
	 */
	private $prettyPrintJson = false;

	/**
	 * @var bool
	 */
	private $pricePrecision = false;

	/**
	 * @var bool
	 */
	private $withoutWebhookContent = false;

	/**
	 * ApiClient constructor.
	 * Создаёт экземпляр коннектора API
	 *
	 * @param string $host хост, на котором располагается API
	 * @param bool $forceHttps форсировать запрос через HTTPS
	 * @param array $credentials логин и пароль пользователя или токен пользователя
	 * @param HttpClient|null $http_client HTTP-клиент
	 *
	 * @throws Exception
	 */
	public function __construct($host, $forceHttps, $credentials, $http_client = null)
	{
		if($host === null || empty($host))
		{
			throw new Exception('Hosts address cannot be empty or null!');
		}

		$host = trim($host);

		if($this->isInvalidCredentials($credentials))
		{
			throw new Exception('Credential login, password or token must be set!');
		}

		while($this->endsWith($host, '/'))
		{
			$host = substr($host, 0, -1);
		}

		if($forceHttps)
		{
			if($this->startsWith($host, 'http://'))
			{
				$host = str_replace('http://', 'https://', $host);
			}
			elseif(!$this->startsWith($host, 'https://'))
			{
				$host = 'https://' . $host;
			}
		}
		elseif(!$this->startsWith($host, 'https://') && !$this->startsWith($host, 'http://'))
		{
			$host = 'http://' . $host;
		}

		$this->host = $host;

		if(is_null($http_client))
		{
			$http_client = new HttpClient();
		}

		$this->setHttpClient($http_client);

		$this->setCredentials($credentials);
	}

	/**
	 * Устанавливает данные доступа, которые используются для авторизации
	 * запросов к API
	 *
	 * @param array $credentials Массив данных для доступа
	 * [
	 *  login - логин в формате <code>[имя_пользователя]@[название_компании]</code>
	 *  password - пароль
	 *  token - Bearer токен авторизации
	 * ]
	 *
	 * @throws Exception
	 */
	public function setCredentials($credentials)
	{
		if(isset($credentials['token']))
		{
			$this->setToken($credentials['token']);
		}
		elseif(isset($credentials['login']) && isset($credentials['password']))
		{
			$this->login = $credentials['login'];
			$this->password = $credentials['password'];
		}
		else
		{
			throw new Exception('Credential login, password or token must be set!');
		}
	}

	/**
	 * Устанавливает Bearer токен авторизации запрсоов к API
	 *
	 * @param string $token Bearer токен авторизации
	 */
	public function setToken($token)
	{
		$this->token = $token;
	}

	/**
	 * Устанавливает пользовательский HTTP-клиент, с помощью которого будут выполняться запросы.
	 *
	 * @param HttpClient $client
	 */
	public function setHttpClient($client)
	{
		$this->httpClient = $client;
	}

	/**
	 * Группа методов API, соответствующих пути <code>/entity/*</code>
	 *
	 * Внимание! Внутри этой цепочки методов каждый сегмент — это отдельный объект. По
	 * возможности избегайте их сохранения в переменные в долгоживущих объектах или не забывайте
	 * про них, так как неосторожное использование может вызвать утечку памяти!
	 *
	 * @return EntityClient
	 */
	public function entity()
	{
		return new EntityClient($this);
	}

	/**
	 * Группа методов API, соответствующих пути <code>/notification/*</code>
	 *
	 * Внимание! Внутри этой цепочки методов каждый сегмент — это отдельный объект. По
	 * возможности избегайте их сохранения в переменные в долгоживущих объектах или не забывайте
	 * про них, так как неосторожное использование может вызвать утечку памяти!
	 */
	public function notification()
	{
		return new NotificationClient($this);
	}

	/**
	 * @return bool
	 */
	public function isPrettyPrintJson()
	{
		return $this->prettyPrintJson;
	}

	/**
	 * @param $prettyPrintJson
	 *
	 * @return $this
	 */
	public function setPrettyPrintJson($prettyPrintJson)
	{
		$this->prettyPrintJson = $prettyPrintJson;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isPricePrecision()
	{
		return $this->pricePrecision;
	}

	/**
	 * @param $pricePrecision
	 *
	 * @return $this
	 */
	public function setPricePrecision($pricePrecision)
	{
		$this->pricePrecision = $pricePrecision;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isWithoutWebhookContent()
	{
		return $this->withoutWebhookContent;
	}

	/**
	 * @param $withoutWebhookContent
	 *
	 * @return $this
	 */
	public function setWithoutWebhookContent($withoutWebhookContent)
	{
		$this->withoutWebhookContent = $withoutWebhookContent;
		return $this;
	}

	/**
	 * @return HttpClient
	 */
	public function getHttpClient()
	{
		return $this->httpClient;
	}

	/**
	 * @return string
	 */
	public function getHost()
	{
		return $this->host;
	}

	/**
	 * @return string
	 */
	public function getLogin()
	{
		return $this->login;
	}

	/**
	 * @return string
	 */
	public function getPassword()
	{
		return $this->password;
	}

	/**
	 * @return string
	 */
	public function getToken()
	{
		return $this->token;
	}

	/**
	 * @param array $credentials
	 *
	 * @return bool
	 */
	private function isInvalidCredentials($credentials) // todo: test connecting with param
	{
		return (!isset($credentials['login']) && !isset($credentials['password'])) && !isset($credentials['token']);
	}
}
