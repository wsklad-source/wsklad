<?php namespace Digiom\ApiMoySklad\Clients\Endpoints;

use Exception;
use Digiom\ApiMoySklad\Entities\MetaEntity;
use Digiom\ApiMoySklad\Utils\HttpRequestExecutor;

/**
 * Trait PostEndpoint
 *
 * @package Digiom\ApiMoySklad\Clients\Endpoints
 */
trait PostEndpoint
{
	/**
	 * @param MetaEntity $newEntity
	 *
	 * @return MetaEntity
	 * @throws Exception
	 */
	public function create(MetaEntity $newEntity)
	{
		/**
		 *  MetaHrefUtils.fillMeta(newEntity, api().getHost() + API_PATH);
		T responseEntity = HttpRequestExecutor.
		path(api(), path()).
		body(newEntity).
		post((Class<T>) entityClass());

		newEntity.set(responseEntity);
		return newEntity;
		 */
		return HttpRequestExecutor::path($this->api(), $this->path())->body($newEntity)->post($this->entityClass());
	}
}
