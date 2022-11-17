<?php namespace Digiom\ApiMoySklad\Clients\Endpoints;

use Exception;
use Digiom\ApiMoySklad\Clients\EntityClientBase;

/**
 * Trait DocumentMetadataEndpoint
 *
 * @package Digiom\ApiMoySklad\Clients\Endpoints
 */
trait DocumentMetadataEndpoint
{
	/**
	 * @return DocumentMetadataClient
	 * @throws Exception
	 */
	public function metadata()
	{
		if(get_parent_class($this) !== EntityClientBase::class)
		{
			throw new Exception('The trait cannot be used outside the EntityClientBase class');
		}

		return new DocumentMetadataClient($this->api(), $this->path(), $this->metaEntityClass());
	}
}
