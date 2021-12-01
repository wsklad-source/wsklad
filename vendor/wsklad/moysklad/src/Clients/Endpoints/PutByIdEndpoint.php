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
 * Trait PutByIdEndpoint
 *
 * @package Wsklad\MoySklad\Clients\Endpoints
 */
trait PutByIdEndpoint
{
	/**
	 * @param $id
	 * @param $updatedEntity
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function updateById($id, $updatedEntity)
	{
		if(get_parent_class($this) !== EntityClientBase::class)
		{
			throw new Exception('The trait cannot be used outside the EntityClientBase class');
		}

		if($id instanceof MetaEntity)
		{
			return $this->updateById($id->getId(), $updatedEntity);
		}

		return HttpRequestExecutor::path($this->api(), $this->path() . $id)->body($updatedEntity)->put($this->entityClass());
	}
}
