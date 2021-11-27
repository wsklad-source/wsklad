<?php
/**
 * Namespace
 */
namespace Wsklad\MoySklad\Clients\Endpoints;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Exception;
use Wsklad\MoySklad\Clients\EntityClientBase;
use Wsklad\MoySklad\Utils\HttpRequestExecutor;

/**
 * Trait GetEndpoint
 *
 * @package Wsklad\MoySklad\Clients\Endpoints
 */
trait GetEndpoint
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

		return HttpRequestExecutor::path($this->api(), $this->path())->apiParams($params)->get($this->entityClass());
	}
}
