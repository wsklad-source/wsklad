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
use Wsklad\MoySklad\Utils\HttpRequestExecutor;

/**
 * Trait DeleteEndpoint
 *
 * @package Wsklad\MoySklad\Clients\Endpoints
 */
trait DeleteEndpoint
{
	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function delete()
	{
		if(get_parent_class($this) !== EntityClientBase::class)
		{
			throw new Exception('The trait cannot be used outside the EntityClientBase class');
		}

		return HttpRequestExecutor::path($this->api(), $this->path())->delete();
	}
}
