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
use Wsklad\Api\MoySklad\Entities\MetaEntity;
use Wsklad\Api\MoySklad\Utils\HttpRequestExecutor;

/**
 * Trait DeleteByIdEndpoint
 *
 * @package Wsklad\Api\MoySklad\Clients\Endpoints
 */
trait DeleteByIdEndpoint
{
	/**
	 * @param $value
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function delete($value)
	{
		if(get_parent_class($this) !== EntityClientBase::class)
		{
			throw new Exception('The trait cannot be used outside the EntityClientBase class');
		}

		if($value instanceof MetaEntity)
		{
			return $this->delete($value->getId());
		}

		return HttpRequestExecutor::path($this->api(), $this->path() . $value)->delete();
	}
}
