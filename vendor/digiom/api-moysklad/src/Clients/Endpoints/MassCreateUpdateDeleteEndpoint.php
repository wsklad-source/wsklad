<?php namespace Digiom\ApiMoySklad\Clients\Endpoints;

use RuntimeException;
use Digiom\ApiMoySklad\Clients\EntityClientBase;
use Digiom\ApiMoySklad\Utils\HttpRequestExecutor;

/**
 * Trait MassCreateUpdateDeleteEndpoint
 *
 * @package Digiom\ApiMoySklad\Clients\Endpoints
 */
trait MassCreateUpdateDeleteEndpoint
{
	/**
	 * @param $entities
	 *
	 * @return mixed
	 */
	public function delete($entities)
	{
		if(get_parent_class($this) !== EntityClientBase::class)
		{
			throw new RuntimeException('The trait cannot be used outside the EntityClientBase class');
		}

		// entities.forEach(newEntity -> MetaHrefUtils.fillMeta(newEntity, api().getHost() + API_PATH));

		return HttpRequestExecutor::path($this->api(), $this->path() . 'delete')->body($entities)->postList(MassDeleteResponse::class);
	}
}
