<?php namespace Digiom\ApiMoySklad\Clients\Endpoints;

use Exception;
use Digiom\ApiMoySklad\Clients\EntityClientBase;
use Digiom\ApiMoySklad\Utils\HttpRequestExecutor;

/**
 * Trait GetPlainListEndpoint
 *
 * @package Digiom\ApiMoySklad\Clients\Endpoints
 */
trait GetPlainListEndpoint
{
	/**
	 * @param array $params
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function getPlainList($params = [])
	{
		if(get_parent_class($this) !== EntityClientBase::class)
		{
			throw new Exception('The trait cannot be used outside the EntityClientBase class');
		}

		return HttpRequestExecutor::path($this->api(), $this->path())->apiParams($params)->plainList($this->getEntityClass());
	}
}
