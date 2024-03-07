<?php namespace Digiom\ApiMoySklad\Utils;

use Exception;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Digiom\Psr7wp\HttpClient;
use Digiom\Psr7wp\RequestDelete;
use Digiom\Psr7wp\RequestGet;
use Digiom\Psr7wp\RequestPost;
use Digiom\Psr7wp\RequestPut;
use Digiom\ApiMoySklad\Utils\Params\ApiParam;
use Digiom\ApiMoySklad\Client;
use Digiom\ApiMoySklad\Entities\MetaEntity;

/**
 * Class HttpRequestExecutor
 *
 * @package Digiom\ApiMoySklad\Utils
 */
final class HttpRequestExecutor
{
	use StringsTrait;

	/**
	 * Local constants
	 */
	const TYPE_PATH = 'path';
	const TYPE_URL = 'url';

	/**
	 * @var string
	 */
	private $hostApiPath;

	/**
	 * @var string
	 */
	private $url;

	/**
	 * @var ApiParam[]
	 */
	private $apiParams = [];

	/**
	 * @var array
	 */
	private $query = [];

	/**
	 * @var array
	 */
	private $headers =
	[
		'Content-Type' => 'application/json',
        'Accept-Encoding' => 'gzip',
		'Accept' => 'application/json;charset=utf-8',
	];

	/**
	 * @var HttpClient
	 */
	private $httpClient;

	/**
	 * @var mixed
	 */
	private $body;

	/**
	 * HttpRequestExecutor constructor.
	 *
	 * @param Client $apiClient
	 * @param string $url
	 * @param string $type
	 */
	private function __construct(Client $apiClient, string $url, string $type = self::TYPE_PATH)
	{
		if(is_null($apiClient))
		{
			throw new InvalidArgumentException('Для выполнения запроса к API нужен проинициализированный экземпляр ApiClient!');
		}

		switch($type)
		{
			case static::TYPE_PATH:
				$this->httpClient = $apiClient->getHttpClient();
				$this->hostApiPath = $apiClient->getHost() . Constants::API_PATH;
				$this->url = $this->hostApiPath . $url;
				$this->auth($apiClient);

				if($apiClient->isPrettyPrintJson())
				{
					$this->header('Lognex-Pretty-Print-JSON', 'true');
				}
				if($apiClient->isPricePrecision())
				{
					$this->header('X-Lognex-Precision', 'true');
				}
				if($apiClient->isWithoutWebhookContent())
				{
					$this->header('X-Lognex-WebHook-Disable', 'true');
				}
				break;
			case static::TYPE_URL:
				$this->httpClient = $apiClient->getHttpClient();
				$this->hostApiPath = '';
				$this->url = $url;
				break;
		}
	}

	/**
	 * Создаёт билдер запроса к URL
	 *
	 * @param Client $api
	 * @param string $url
	 *
	 * @return HttpRequestExecutor
	 */
	public static function url(Client $api, string $url)
	{
		return new static($api, $url, self::TYPE_URL);
	}

	/**
	 * Создаёт билдер запроса к методу API
	 *
	 * @param Client $api Проинициализированный экземпляр класса с данными API
	 * @param string $path путь к методу API (например <code>/entity/counterparty/metadata</code>)
	 *
	 * @return mixed
	 */
	public static function path(Client $api, string $path)
	{
		return new static($api, $path);
	}

	/**
	 * Добавляет авторизационный заголовок с данными доступа API
	 *
	 * @param Client $api
	 *
	 * @return HttpRequestExecutor
	 */
	private function auth(Client $api): HttpRequestExecutor
	{
		if($api->getToken())
		{
			return $this->header('Authorization', 'Bearer ' . $api->getToken());
		}

		return $this->header('Authorization', 'Basic ' . base64_encode($api->getLogin() . ':' . $api->getPassword()));
	}

	/**
	 * Добавление параметра в строку запроса после URL в формате <code>key=value&amp;</code>.
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return HttpRequestExecutor
	 */
	public function query(string $key, string $value): HttpRequestExecutor
	{
		if ('' !== $key)
		{
			$this->query[$key] = $value;
		}

		return $this;
	}

	/**
	 * Добавление параметра в заголовки запроса
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return HttpRequestExecutor
	 */
	public function header(string $key, string $value): HttpRequestExecutor
	{
		if('' !== $key)
		{
			$this->headers[$key] = $value;
		}

		return $this;
	}

	/**
	 * Добавить параметр API (например order, filter, offset и т. п.)
	 *
	 * @param ApiParam[] $params
	 *
	 * @return HttpRequestExecutor
	 */
	public function apiParams(array $params): HttpRequestExecutor
	{
		if(!is_null($this->apiParams) && count($params) > 0)
		{
			$this->apiParams = $params;
		}

		return $this;
	}

	/**
	 * Добавление в тело запроса (для запросов, поддерживающих отправку данных в теле)
	 *
	 * @param MetaEntity $body
	 *
	 * @return HttpRequestExecutor
	 */
	public function body($body): HttpRequestExecutor
	{
		$this->body = $body;
		return $this;
	}

	/**
	 * Строит полный URL запроса с учётом добавленных ранее параметров запроса
	 *
	 * @return string
	 */
	private function getFullUrl(): string
	{
		if(count($this->apiParams) < 1)
		{
			return $this->url;
		}

		$paramTypes = array_unique(array_column($this->apiParams, 'type'));

		foreach($paramTypes as $paramType)
		{
			$this->query[urlencode($paramType)] = ApiParam::renderStringQueryFromList($paramType, $this->apiParams, $this->hostApiPath);
		}

		return $this->url . '?' . http_build_query($this->query);
	}

	/**
	 * Добавляет заголовки в запрос
	 *
	 * @param RequestInterface $request
	 */
	private function applyHeaders(RequestInterface $request)
	{
		/*
		 * for (Map.Entry<String, Object> e : headers.entrySet())
		 * {
		 *  request.setHeader(e.getKey(), String.valueOf(e.getValue()));
		 * }
		 */
	}

	/**
	 * Выполняет созданный запрос
	 *
	 * @param RequestInterface $request
	 *
	 * @return string - тело ответа
	 * @throws ClientException Когда возникла ошибка API
	 */
	private function executeRequest(RequestInterface $request)
	{
		// logger.debug("Выполнение запроса  {} {}...", request.getMethod(), request.getURI());
		try
		{
			$response = $this->httpClient->sendRequest($request);
			if($this->isOkResponse($response))
			{
				throw new ClientException($request->getMethod() . ' ' . $request->getUri(), $response->getStatusCode(), $response->getReasonPhrase());
			}

			return $response->getBody()->getContents();
		}
		catch(Exception $e)
		{
			$message = $e->getMessage();

			if($e instanceof ClientException)
			{
				//$message .= ' Response content: ' . $e->getResponse()->getBody()->getContents();
				$message .= '';
			}

			throw new ClientException($request->getMethod() . ' ' . $request->getUri(), $e->getCode(), $message);
		}
	}

	/**
	 * Good response
	 *
	 * @param $response
	 *
	 * @return bool
	 */
	public function isOkResponse($response): bool
	{
		$statusCode = (int) $response->getStatusCode();

		return $statusCode !== 200 && $statusCode !== 201 && $statusCode !== 204;
	}

	/**
	 * Выполняет GET-запрос с указанными ранее параметрами и конвертирует ответ в объект указанного класса
	 *
	 * @param string $className Класс, в который нужно конвертировать ответ на запрос
	 *
	 * @return mixed
	 * @throws ClientException Когда возникла ошибка API
	 */
	public function get(string $className = '')
	{
		$request = new RequestGet($this->getFullUrl(), $this->headers);

		$response = $this->executeRequest($request);

		if($className)
		{
			// todo:gson.fromJson(get(), cl)
		}

		return $response;
	}

	/**
	 * Выполняет GET-запрос с указанными ранее параметрами и конвертирует ответ в <b>массив</b> объектов указанного класса
	 *
	 * @param string $className Класс объектов массива, в который нужно сконвертировать ответ на запрос
	 *
	 * @return string Тело ответа
	 * @throws ClientException Когда возникла ошибка API
	 */
	public function lists(string $className)
	{
		// todo: return gson.fromJson(get(), TypeToken.getParameterized(ListEntity.class, cl).getType());
	}

	/**
	 * Выполняет GET-запрос с указанными ранее параметрами и конвертирует ответ в <b>список</b> объектов указанного класса
	 *
	 * @param string $className Класс объектов списка, в который нужно сконвертировать ответ на запрос
	 *
	 * @return string Тело ответа
	 * @throws ClientException Когда возникла ошибка API
	 */
	public function plainLists(string $className): string
	{
		// todo: gson.fromJson(get(), TypeToken.getParameterized(List.class, cl).getType())
	}

	/**
	 * Выполняет POST-запрос с указанными ранее параметрами
	 *
	 * @param string $className
	 *
	 * @return string Тело ответа
	 * @throws ClientException Когда возникла ошибка API
	 */
	public function post($className)
	{
		$strBody = null;
		if(!is_null($this->body))
		{
			$strBody = json_encode($this->body);
		}

		$request = new RequestPost($this->getFullUrl(), $this->headers, $strBody);

		$response = $this->executeRequest($request);

		if($className)
		{
			// todo: gson.fromJson(post(), cl)
		}

		return $response;
	}

	/**
	 * Выполняет POST-запрос с указанными ранее параметрами и сохраняет ответ в указанный файл
	 *
	 * @return mixed Тело ответа
	 * @throws ClientException когда возникла ошибка API
	 */
	public function postAndSaveTo($file)
	{
		/*
		    HttpPost request = new HttpPost(getFullUrl());
            applyHeaders(request);

	        if (body != null) {
	            String strBody = gson.toJson(body);
	            logger.debug("Тело запроса        {} {}: {}", request.getMethod(), request.getURI(), strBody);
	            StringEntity requestEntity = new StringEntity(strBody, ContentType.APPLICATION_JSON);
	            request.setEntity(requestEntity);
	        }

	        byte[] data = executeByteRequest(request);
	        FileUtils.writeByteArrayToFile(file, data);
	        return file;
		 */
	}

	/**
	 * Выполняет POST-запрос с указанными ранее параметрами и конвертирует ответ в <b>массив</b> объектов указанного класса
	 *
	 * @param string $className Класс объектов массива, в который нужно сконвертировать ответ на запрос
	 *
	 * @return string Тело ответа
	 * @throws ClientException Когда возникла ошибка API
	 */
	public function postList($className)
	{
		// gson.fromJson(post(), TypeToken.getParameterized(List.class, cl).getType());
	}

	/**
	 * Выполняет DELETE-запрос с указанными ранее параметрами
	 *
	 * @throws ClientException Когда возникла ошибка API
	 */
	public function delete()
	{
		$request = new RequestDelete($this->getFullUrl(), $this->headers);

		return $this->executeRequest($request);
	}

	/**
	 * Выполняет PUT-запрос с указанными ранее параметрами и конвертирует ответ в объект указанного класса
	 *
	 * @param string $className
	 *
	 * @return mixed Тело ответа
	 * @throws ClientException Когда возникла ошибка API
	 */
	public function put(string $className = '')
	{
		$strBody = null;
		if(!is_null($this->body))
		{
			$strBody = json_encode($this->body);
		}

		$request = new RequestPut($this->getFullUrl(), $this->headers, $strBody);

		$response = $this->executeRequest($request);

		if($className)
		{
			// todo: перевод ответа в указанный класс - gson.fromJson(put(), cl)
		}

		return $response;
	}
}
