<?php
/**
 * Namespace
 */
namespace Wsklad\MoySklad\Clients;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Wsklad\MoySklad\ApiClient;
use Wsklad\MoySklad\Clients\Endpoints\DeleteByIdEndpoint;
use Wsklad\MoySklad\Clients\Endpoints\GetByIdEndpoint;
use Wsklad\MoySklad\Clients\Endpoints\GetListEndpoint;
use Wsklad\MoySklad\Clients\Endpoints\HasAccessManagmentEndpoint;
use Wsklad\MoySklad\Clients\Endpoints\HasPermissionsEndpoint;
use Wsklad\MoySklad\Clients\Endpoints\MassCreateUpdateDeleteEndpoint;
use Wsklad\MoySklad\Clients\Endpoints\MetadataAttributeEndpoint;
use Wsklad\MoySklad\Clients\Endpoints\MetadataEndpoint;
use Wsklad\MoySklad\Clients\Endpoints\PostEndpoint;
use Wsklad\MoySklad\Clients\Endpoints\PutByIdEndpoint;
use Wsklad\MoySklad\Entities\Agents\Employee;
use Wsklad\MoySklad\Responses\Metadata\MetadataAttributeSharedResponse;

/**
 * Class EmployeeClient
 *
 * @package Wsklad\MoySklad\Clients
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
	 * @param ApiClient $api
	 */
	public function __construct(ApiClient $api)
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
