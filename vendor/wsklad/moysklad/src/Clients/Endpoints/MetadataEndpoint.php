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
 * Trait MetadataEndpoint
 *
 * @package Wsklad\MoySklad\Clients\Endpoints
 */
trait MetadataEndpoint
{
	/**
	 * @return mixed
	 * @throws RuntimeException
	 */
	public function metadata()
	{
		if(get_parent_class($this) !== EntityClientBase::class)
		{
			throw new RuntimeException('The trait cannot be used outside the EntityClientBase class');
		}

		return HttpRequestExecutor::path($this->api(), $this->path() . 'metadata')->get($this->metaEntityClass());
	}
}
