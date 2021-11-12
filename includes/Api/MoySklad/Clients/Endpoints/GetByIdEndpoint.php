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
use Wsklad\Api\MoySklad\Entities\MetaEntity;
use Wsklad\Api\MoySklad\Utils\HttpRequestExecutor;

/**
 * Trait GetByIdEndpoint
 *
 * @package Wsklad\Api\MoySklad\Clients\Endpoints
 */
trait GetByIdEndpoint
{
	/**
	 * @param array $params
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function get($id, $params = [])
	{
		if(get_parent_class($this) !== EntityClientBase::class)
		{
			throw new Exception('The trait cannot be used outside the EntityClientBase class');
		}

		if($id instanceof MetaEntity)
		{
			return $this->get($id->getId(), $params);
		}

		return HttpRequestExecutor::path($this->api(), $this->path() . $id)->apiParams($params)->get($this->getEntityClass());
	}
}
