<?php namespace Digiom\ApiMoySklad\Clients;

use Digiom\ApiMoySklad\Client;
use Digiom\ApiMoySklad\Clients\Endpoints\DeleteByIdEndpoint;
use Digiom\ApiMoySklad\Clients\Endpoints\GetByIdEndpoint;
use Digiom\ApiMoySklad\Clients\Endpoints\GetListEndpoint;
use Digiom\ApiMoySklad\Clients\Endpoints\HasAccessManagmentEndpoint;
use Digiom\ApiMoySklad\Clients\Endpoints\HasPermissionsEndpoint;
use Digiom\ApiMoySklad\Clients\Endpoints\MassCreateUpdateDeleteEndpoint;
use Digiom\ApiMoySklad\Clients\Endpoints\MetadataAttributeEndpoint;
use Digiom\ApiMoySklad\Clients\Endpoints\MetadataEndpoint;
use Digiom\ApiMoySklad\Clients\Endpoints\PostEndpoint;
use Digiom\ApiMoySklad\Clients\Endpoints\PutByIdEndpoint;
use Digiom\ApiMoySklad\Entities\Agents\Employee;
use Digiom\ApiMoySklad\Responses\Metadata\MetadataAttributeSharedResponse;

/**
 * Class EmployeeClient
 *
 * @package Digiom\ApiMoySklad\Clients
 */
final class EmployeeClient extends EntityClientBase
{
	use GetListEndpoint,
		PostEndpoint,
		DeleteByIdEndpoint,
		MetadataEndpoint,
		MetadataAttributeEndpoint,
		GetByIdEndpoint,
		PutByIdEndpoint,
		MassCreateUpdateDeleteEndpoint,
		HasPermissionsEndpoint,
		HasAccessManagmentEndpoint;

	/**
	 * EmployeeClient constructor.
	 *
	 * @param Client $api
	 */
	public function __construct(Client $api)
	{
		parent::__construct($api, '/entity/employee/');
	}

	/**
	 * @return string
	 */
	public function entityClass()
	{
		return Employee::class;
	}

	/**
	 * @return string
	 */
	public function metaEntityClass()
	{
		return MetadataAttributeSharedResponse::class;
	}
}
