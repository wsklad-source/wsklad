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
use Exception;
use Wsklad\MoySklad\Entities\MetaEntity;
use Wsklad\MoySklad\Utils\HttpRequestExecutor;

/**
 * Trait PostEndpoint
 *
 * @package Wsklad\MoySklad\Clients\Endpoints
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
