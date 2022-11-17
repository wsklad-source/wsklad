<?php namespace Digiom\ApiMoySklad\Clients\Endpoints;

use Exception;
use Digiom\ApiMoySklad\Clients\EntityClientBase;
use Digiom\ApiMoySklad\Utils\HttpRequestExecutor;

/**
 * Trait GetEndpoint
 *
 * @package Digiom\ApiMoySklad\Clients\Endpoints
 */
trait GetEndpoint
{
	/**
	 * @param array $params
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function get($params = [])
	{
		if(get_parent_class($this) !== EntityClientBase::class)
		{
			throw new Exception('The trait cannot be used outside the EntityClientBase class');
		}

		return HttpRequestExecutor::path($this->api(), $this->path())->apiParams($params)->get($this->entityClass());
	}
}
