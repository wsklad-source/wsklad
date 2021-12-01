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
use Wsklad\MoySklad\Clients\Endpoints\MassCreateUpdateDeleteEndpoint;
use Wsklad\MoySklad\Clients\Endpoints\PostEndpoint;
use Wsklad\MoySklad\Clients\Endpoints\PutByIdEndpoint;
use Wsklad\MoySklad\Entities\ExpenseItem;

/**
 * Class ExpenseItemClient
 *
 * @package Wsklad\MoySklad\Clients
 */
final class ExpenseItemClient extends EntityClientBase
{
	use GetListEndpoint,
		PostEndpoint,
		DeleteByIdEndpoint,
		GetByIdEndpoint,
		PutByIdEndpoint,
		MassCreateUpdateDeleteEndpoint;

	/**
	 * ExpenseItemClient constructor.
	 *
	 * @param ApiClient $api
	 */
	public function __construct(ApiClient $api)
	{
		parent::__construct($api, '/entity/expenseitem/');
	}

	/**
	 * @return string
	 */
	public function entityClass()
	{
		return ExpenseItem::class;
	}
}
