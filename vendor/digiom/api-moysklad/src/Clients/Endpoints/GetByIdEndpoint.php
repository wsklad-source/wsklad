<?php namespace Digiom\ApiMoySklad\Clients\Endpoints;

use Exception;
use Digiom\ApiMoySklad\Clients\EntityClientBase;
use Digiom\ApiMoySklad\Entities\MetaEntity;
use Digiom\ApiMoySklad\Utils\HttpRequestExecutor;

/**
 * Trait GetByIdEndpoint
 *
 * @package Digiom\ApiMoySklad\Clients\Endpoints
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
