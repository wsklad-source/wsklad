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
use Wsklad\MoySklad\Clients\Endpoints\GetListEndpoint;
use Wsklad\MoySklad\Entities\Discounts\Discount;

/**
 * Class DiscountClient
 *
 * @package Wsklad\MoySklad\Clients
 */
final class DiscountClient extends EntityClientBase
{
	use GetListEndpoint;

	/**
	 * DiscountClient constructor.
	 *
	 * @param ApiClient $api
	 */
	public function __construct(ApiClient $api)
	{
		parent::__construct($api, '/entity/discount/');
	}

	/**
	 * @return string
	 */
	public function entityClass()
	{
		return Discount::class;
	}
}
