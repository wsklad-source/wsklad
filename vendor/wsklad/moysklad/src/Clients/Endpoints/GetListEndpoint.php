<?php
/**
 * Namespace
 */
namespace Wsklad\MoySklad\Clients\Endpoints;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use RuntimeException;
use Wsklad\MoySklad\Clients\EntityClientBase;
use Wsklad\MoySklad\Entities\ListEntity;
use Wsklad\MoySklad\Utils\HttpRequestExecutor;

/**
 * Trait GetListEndpoint
 *
 * @package Wsklad\MoySklad\Clients\Endpoints
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
