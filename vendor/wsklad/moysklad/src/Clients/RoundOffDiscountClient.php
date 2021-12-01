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
use Wsklad\MoySklad\Clients\Endpoints\GetByIdEndpoint;
use Wsklad\MoySklad\Clients\Endpoints\PutByIdEndpoint;
use Wsklad\MoySklad\Entities\Discounts\RoundOffDiscount;

/**
 * Class RoundOffDiscountClient
 *
 * @package Wsklad\MoySklad\Clients
 */
final class RoundOffDiscountClient extends EntityClientBase
{
    use GetByIdEndpoint,
	    PutByIdEndpoint;

    /**
     * RoundOffDiscountClient constructor.
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
        return RoundOffDiscount::class;
    }
}
