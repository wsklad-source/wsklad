<?php namespace Digiom\ApiMoySklad\Clients\Endpoints;

use RuntimeException;
use Digiom\ApiMoySklad\Clients\EntityClientBase;
use Digiom\ApiMoySklad\Utils\HttpRequestExecutor;

/**
 * Trait PutEndpoint
 *
 * @package Digiom\ApiMoySklad\Clients\Endpoints
 */
trait PutEndpoint
{
	/**
	 * @param $updatedEntity
	 *
	 * @return mixed
	 * @throws RuntimeException
	 */
	public function update($updatedEntity)
	{
		if(get_parent_class($this) !== EntityClientBase::class)
		{
			throw new RuntimeException('The trait cannot be used outside the EntityClientBase class');
		}

		// MetaHrefUtils.fillMeta(updatedEntity, api().getHost() + API_PATH);

		return HttpRequestExecutor::path($this->api(), $this->path())->body($updatedEntity)->put($this->entityClass());
	}
}
