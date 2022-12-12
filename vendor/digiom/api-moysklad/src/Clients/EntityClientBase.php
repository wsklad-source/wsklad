<?php namespace Digiom\ApiMoySklad\Clients;

use Digiom\ApiMoySklad\Client;
use Digiom\ApiMoySklad\Clients\Endpoints\Endpoint;

/**
 * Class EntityClientBase
 *
 * @package Digiom\ApiMoySklad\Clients
 */
abstract class EntityClientBase implements Endpoint
{
	/**
	 * @var Client
	 */
	protected $api;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * EntityClientBase constructor.
	 *
	 * @param Client $api
	 *
	 * @param string $path
	 */
	public function __construct($api, $path)
	{
		$this->api = $api;
		$this->path = $path;
	}

	/**
	 * @return Client
	 */
	public function api():Client
	{
		return $this->api;
	}

	/**
	 * @return string
	 */
	public function path(): string
	{
		return $this->path;
	}

	/**
	 * @return string|null
	 */
	public function entityClass()
	{
		return null;
	}

	/**
	 * @return string|null
	 */
	public function metaEntityClass()
	{
		return null;
	}

	/**
	 * @return string|null
	 */
	public function positionEntityClass()
	{
		return null;
	}
}
