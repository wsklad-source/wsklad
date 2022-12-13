<?php namespace Digiom\ApiMoySklad\Clients\Endpoints;

use Digiom\ApiMoySklad\Utils\HttpRequestExecutor;

/**
 * Trait HasAccessManagmentEndpoint
 *
 * @package Digiom\ApiMoySklad\Clients\Endpoints
 */
trait HasAccessManagmentEndpoint
{
	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	public function resetPassword($id)
	{
		return HttpRequestExecutor::path($this->api(), $this->path() . $id . '/access/resetpassword')->put();
	}

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	public function deactivate($id)
	{
		return HttpRequestExecutor::path($this->api(), $this->path() . $id . '/access/deactivate')->put();
	}

	/**
	 * @param $id
	 * @param $employeePermission
	 *
	 * @return mixed
	 */
	public function activate($id, $employeePermission)
	{
		return HttpRequestExecutor::path($this->api(), $this->path() . $id . '/access/activate')->body($employeePermission)->put(MailActivationRequired::class);
	}
}
