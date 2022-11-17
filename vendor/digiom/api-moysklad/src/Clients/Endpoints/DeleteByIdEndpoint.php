<?php namespace Digiom\ApiMoySklad\Clients\Endpoints;

use Exception;
use Digiom\ApiMoySklad\Clients\EntityClientBase;
use Digiom\ApiMoySklad\Entities\MetaEntity;
use Digiom\ApiMoySklad\Utils\HttpRequestExecutor;

/**
 * Trait DeleteByIdEndpoint
 *
 * @package Digiom\ApiMoySklad\Clients\Endpoints
 */
trait DeleteByIdEndpoint
{
	/**
	 * @param int|MetaEntity $value
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function deleteById($value)
	{
		if(get_parent_class($this) !== EntityClientBase::class)
		{
			throw new Exception('The trait cannot be used outside the EntityClientBase class');
		}

		if($value instanceof MetaEntity)
		{
			return $this->deleteById($value->getId());
		}

		return HttpRequestExecutor::path($this->api(), $this->path() . $value)->delete();
	}
}
