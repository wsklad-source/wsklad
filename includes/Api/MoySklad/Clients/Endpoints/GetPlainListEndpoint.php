<?php
/**
 * Namespace
 */
namespace Wsklad\Api\MoySklad\Clients\Endpoints;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Exception;
use Wsklad\Api\MoySklad\Clients\EntityClientBase;
use Wsklad\Api\MoySklad\Utils\HttpRequestExecutor;

/**
 * Trait GetPlainListEndpoint
 *
 * @package Wsklad\Api\MoySklad\Clients\Endpoints
 */
trait GetPlainListEndpoint
{
	/**
	 * @param array $params
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function get($params = [])
	{
		if(get_parent_class($this) !== EntityClientBase::class)
		{
			throw new Exception('The trait cannot be used outside the EntityClientBase class');
		}

		return HttpRequestExecutor::path($this->api(), $this->path())->apiParams($params)->plainList($this->getEntityClass());
	}
}
