<?php namespace Digiom\ApiMoySklad;

use Exception;
use RuntimeException;
use Digiom\ApiMoySklad\Utils\HttpRequestExecutor;
use Digiom\Psr7wp\HttpClient;
use Digiom\ApiMoySklad\Clients\EntityClient;
use Digiom\ApiMoySklad\Utils\StringsTrait;

/**
 * Class Client
 *
 * @package Digiom\ApiMoySklad
 */
class Client
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
	 * @param string $host Хост, на котором располагается API
	 * @param bool $forceHttps Форсировать запрос через HTTPS
	 * @param array $credentials Логин и пароль пользователя или токен пользователя
	 * @param HttpClient|null $http_client HTTP-клиент
	 *
	 * @throws Exception
	 */
	public function __construct(string $host, bool $forceHttps, array $credentials, HttpClient $http_client = null)
	{
		if(empty($host))
		{
			throw new RuntimeException('Hosts address cannot be empty or null!');
		}

		$host = trim($host);

		if($this->isInvalidCredentials($credentials))
		{
			throw new RuntimeException('Credential login, password or token must be set!');
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
	public function setCredentials(array $credentials)
	{
		if(isset($credentials['token']))
		{
			$this->setToken($credentials['token']);
		}
		elseif(isset($credentials['login'], $credentials['password']))
		{
			$this->login = $credentials['login'];
			$this->password = $credentials['password'];
		}
		else
		{
			throw new RuntimeException('Credential login, password or token must be set!');
		}
	}

	/**
	 * Устанавливает Bearer токен авторизации запросов к API
	 *
	 * @param string $token Bearer токен авторизации
	 */
	public function setToken(string $token)
	{
		$this->token = $token;
	}

	/**
	 * Устанавливает пользовательский HTTP-клиент, с помощью которого будут выполняться запросы.
	 *
	 * @param HttpClient $client
	 */
	public function setHttpClient(HttpClient $client)
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
	public function entity(): EntityClient
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
	public function notification(): NotificationClient
	{
		return new NotificationClient($this);
	}

	/**
	 * @return bool
	 */
	public function isPrettyPrintJson(): bool
	{
		return $this->prettyPrintJson;
	}

	/**
	 * @param $prettyPrintJson
	 *
	 * @return $this
	 */
	public function setPrettyPrintJson($prettyPrintJson): Client
	{
		$this->prettyPrintJson = $prettyPrintJson;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isPricePrecision(): bool
	{
		return $this->pricePrecision;
	}

	/**
	 * @param $pricePrecision
	 *
	 * @return $this
	 */
	public function setPricePrecision($pricePrecision): Client
	{
		$this->pricePrecision = $pricePrecision;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isWithoutWebhookContent(): bool
	{
		return $this->withoutWebhookContent;
	}

	/**
	 * @param $withoutWebhookContent
	 *
	 * @return $this
	 */
	public function setWithoutWebhookContent($withoutWebhookContent): Client
	{
		$this->withoutWebhookContent = $withoutWebhookContent;

		return $this;
	}

	/**
	 * @return HttpClient
	 */
	public function getHttpClient(): HttpClient
	{
		return $this->httpClient;
	}

	/**
	 * @return string
	 */
	public function getHost(): string
	{
		return $this->host;
	}

	/**
	 * @return string
	 */
	public function getLogin(): string
	{
		return $this->login;
	}

	/**
	 * @return string
	 */
	public function getPassword(): string
	{
		return $this->password;
	}

	/**
	 * @return string
	 */
	public function getToken(): string
	{
		return $this->token;
	}

	/**
	 * @param array $credentials
	 *
	 * @return bool
	 */
	private function isInvalidCredentials(array $credentials): bool // todo: test connecting with param
	{
		return (!isset($credentials['login']) && !isset($credentials['password'])) && !isset($credentials['token']);
	}

	/**
	 * Произвольный запрос к API по пути
	 *
	 * @param string $path
	 *
	 * @return HttpRequestExecutor
	 */
	public function api(string $path): HttpRequestExecutor
	{
		return HttpRequestExecutor::path($this, $path);
	}
}
