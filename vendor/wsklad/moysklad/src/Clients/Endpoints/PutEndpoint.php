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
use Wsklad\MoySklad\Utils\HttpRequestExecutor;

/**
 * Trait PutEndpoint
 *
 * @package Wsklad\MoySklad\Clients\Endpoints
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
