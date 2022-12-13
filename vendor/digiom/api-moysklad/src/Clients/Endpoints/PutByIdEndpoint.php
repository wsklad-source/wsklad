<?php namespace Digiom\ApiMoySklad\Clients\Endpoints;

use Exception;
use Digiom\ApiMoySklad\Clients\EntityClientBase;
use Digiom\ApiMoySklad\Entities\MetaEntity;
use Digiom\ApiMoySklad\Utils\HttpRequestExecutor;

/**
 * Trait PutByIdEndpoint
 *
 * @package Digiom\ApiMoySklad\Clients\Endpoints
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
