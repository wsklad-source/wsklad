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
use Wsklad\MoySklad\Clients\Endpoints\PostEndpoint;
use Wsklad\MoySklad\Clients\Endpoints\PutByIdEndpoint;
use Wsklad\MoySklad\Entities\Discounts\AccumulationDiscount;

/**
 * Class AccumulationDiscountClient
 *
 * @package Wsklad\MoySklad\Clients
 */
class AccumulationDiscountClient extends EntityClientBase
{
    use GetListEndpoint,
	    GetByIdEndpoint,
	    PostEndpoint,
	    PutByIdEndpoint,
	    DeleteByIdEndpoint;

    /**
     * AccumulationDiscountClient constructor.
     *
     * @param ApiClient $api
     */
    public function __construct(ApiClient $api)
    {
        parent::__construct($api, '/entity/accumulationdiscount/');
    }

    /**
     * @return string
     */
    public function entityClass()
    {
        return AccumulationDiscount::class;
    }
}
