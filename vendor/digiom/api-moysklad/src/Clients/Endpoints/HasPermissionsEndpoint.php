<?php namespace Digiom\ApiMoySklad\Clients\Endpoints;

use RuntimeException;
use Digiom\ApiMoySklad\Clients\EntityClientBase;
use Digiom\ApiMoySklad\Utils\HttpRequestExecutor;

/**
 * Trait HasPermissionsEndpoint
 *
 * @package Digiom\ApiMoySklad\Clients\Endpoints
 */
trait HasPermissionsEndpoint
{
	/**
	 * @param $id
	 *
	 * @return mixed
	 * @throws RuntimeException
	 */
	public function getPermissions($id)
	{
		if(get_parent_class($this) !== EntityClientBase::class)
		{
			throw new RuntimeException('The trait cannot be used outside the EntityClientBase class');
		}

		return HttpRequestExecutor::path($this->api(), $this->path() . $id . '/security')->get(EmployeePermission::class);
	}

	/**
	 * @param $id
	 * @param $employeePermissions
	 *
	 * @return mixed
	 * @throws RuntimeException
	 */
	public function updatePermissions($id, $employeePermissions)
	{
		if(get_parent_class($this) !== EntityClientBase::class)
		{
			throw new RuntimeException('The trait cannot be used outside the EntityClientBase class');
		}

		return HttpRequestExecutor::path($this->api(), $this->path() . $id . '/security')->body($employeePermissions)->put(EmployeePermission::class);
	}
}
