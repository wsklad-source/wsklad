<?php namespace Digiom\ApiMoySklad\Clients\Endpoints;

use Exception;
use Digiom\ApiMoySklad\Clients\EntityClientBase;

/**
 * Trait MetadataAttributeEndpoint
 *
 * @package Digiom\ApiMoySklad\Clients\Endpoints
 */
trait MetadataAttributeEndpoint
{
	/**
	 * @throws Exception
	 */
	public function metadataAttributes($id)
	{
		if(get_parent_class($this) !== EntityClientBase::class)
		{
			throw new Exception('The trait cannot be used outside the EntityClientBase class');
		}
	}
}
