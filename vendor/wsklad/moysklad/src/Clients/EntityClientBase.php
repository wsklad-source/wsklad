<?php
/**
 * Namespace
 */
namespace Wsklad\MoySklad\Clients;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Wsklad\MoySklad\ApiClient;
use Wsklad\MoySklad\Clients\Endpoints\Endpoint;

/**
 * Class EntityClientBase
 *
 * @package Wsklad\MoySklad\Clients
 */
abstract class EntityClientBase implements Endpoint
{
	/**
	 * @var ApiClient
	 */
	protected $api;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * EntityClientBase constructor.
	 *
	 * @param ApiClient $api
	 *
	 * @param string $path
	 */
	public function __construct($api, $path)
	{
		$this->api = $api;
		$this->path = $path;
	}

	/**
	 * @return ApiClient
	 */
	public function api()
	{
		return $this->api;
	}

	/**
	 * @return string
	 */
	public function path()
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
