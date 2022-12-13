<?php namespace Digiom\ApiMoySklad\Clients\Endpoints;

use Exception;
use Digiom\ApiMoySklad\Clients\EntityClientBase;
use Digiom\ApiMoySklad\Utils\HttpRequestExecutor;

/**
 * Trait GetBySyncIdEndpoint
 *
 * @package Digiom\ApiMoySklad\Clients\Endpoints
 */
trait GetBySyncIdEndpoint
{
	/**
	 * @param $sync_id
	 * @param array $params
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function getBySyncId($sync_id, $params = [])
	{
		if(get_parent_class($this) !== EntityClientBase::class)
		{
			throw new Exception('The trait cannot be used outside the EntityClientBase class');
		}

		return HttpRequestExecutor::path($this->api(), $this->path() . 'syncid/' . $sync_id)->apiParams($params)->get($this->entityClass());
	}
}
