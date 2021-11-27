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
use Wsklad\MoySklad\Clients\EntityClientBase;
use Wsklad\MoySklad\Entities\MetaEntity;
use Wsklad\MoySklad\Utils\HttpRequestExecutor;

/**
 * Trait DeleteByIdEndpoint
 *
 * @package Wsklad\MoySklad\Clients\Endpoints
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
