<?php
/**
 * Namespace
 */
namespace Wsklad\Api\MoySklad\Clients\Endpoints;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Exception;
use Wsklad\Api\MoySklad\Clients\EntityClientBase;

/**
 * Trait DocumentMetadataEndpoint
 *
 * @package Wsklad\Api\MoySklad\Clients\Endpoints
 */
trait DocumentMetadataEndpoint
{
	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function metadata()
	{
		if(get_parent_class($this) !== EntityClientBase::class)
		{
			throw new Exception('The trait cannot be used outside the EntityClientBase class');
		}

		return new DocumentMetadataClient($this->api(), $this->path(), $this->getMetaEntityClass());
	}
}
