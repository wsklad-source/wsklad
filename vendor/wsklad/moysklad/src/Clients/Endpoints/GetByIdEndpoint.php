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
use Wsklad\MoySklad\Entities\MetaEntity;
use Wsklad\MoySklad\Utils\HttpRequestExecutor;

/**
 * Trait GetByIdEndpoint
 *
 * @package Wsklad\MoySklad\Clients\Endpoints
 */
trait GetByIdEndpoint
{
	/**
	 * @param array $params
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function getById($id, $params = [])
	{
		if(get_parent_class($this) !== EntityClientBase::class)
		{
			throw new Exception('The trait cannot be used outside the EntityClientBase class');
		}

		if($id instanceof MetaEntity)
		{
			return $this->getById($id->getId(), $params);
		}

		return HttpRequestExecutor::path($this->api(), $this->path() . $id)->apiParams($params)->get($this->entityClass());
	}
}
