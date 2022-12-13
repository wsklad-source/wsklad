<?php namespace Digiom\ApiMoySklad\Clients\Endpoints;

use RuntimeException;
use Digiom\ApiMoySklad\Clients\EntityClientBase;
use Digiom\ApiMoySklad\Entities\ListEntity;
use Digiom\ApiMoySklad\Utils\HttpRequestExecutor;

/**
 * Trait GetListEndpoint
 *
 * @package Digiom\ApiMoySklad\Clients\Endpoints
 */
trait GetListEndpoint
{
	/**
	 * @param array $params
	 *
	 * @return mixed
	 * @throws RuntimeException
	 */
	public function getList($params = [])
	{
		if(get_parent_class($this) !== EntityClientBase::class)
		{
			throw new RuntimeException('The trait cannot be used outside the EntityClientBase class');
		}

		return HttpRequestExecutor::path($this->api(), $this->path())->apiParams($params)->get($this->getListEntityClass());
	}

	/**
	 * Класс списка для данной выборки
	 *
	 * @return string
	 */
	protected function getListEntityClass()
	{
		return ListEntity::class;
	}
}
