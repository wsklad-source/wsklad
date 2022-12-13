<?php namespace Digiom\ApiMoySklad\Clients\Endpoints;

use RuntimeException;
use Digiom\ApiMoySklad\Clients\EntityClientBase;
use Digiom\ApiMoySklad\Utils\HttpRequestExecutor;

/**
 * Trait MassCreateUpdateEndpoint
 *
 * @package Digiom\ApiMoySklad\Clients\Endpoints
 */
trait MassCreateUpdateEndpoint
{
	/**
	 * @param $entities
	 *
	 * @return mixed
	 */
	public function createOrUpdate($entities)
	{
		if(get_parent_class($this) !== EntityClientBase::class)
		{
			throw new RuntimeException('The trait cannot be used outside the EntityClientBase class');
		}

		// entities.forEach(newEntity -> MetaHrefUtils.fillMeta(newEntity, api().getHost() + API_PATH));

		return HttpRequestExecutor::path($this->api(), $this->path())->body($entities)->postList($this->entityClass());
	}
}
