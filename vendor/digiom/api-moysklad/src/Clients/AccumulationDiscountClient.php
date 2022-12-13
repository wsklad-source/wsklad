<?php namespace Digiom\ApiMoySklad\Clients;

use Digiom\ApiMoySklad\Client;
use Digiom\ApiMoySklad\Clients\Endpoints\DeleteByIdEndpoint;
use Digiom\ApiMoySklad\Clients\Endpoints\GetByIdEndpoint;
use Digiom\ApiMoySklad\Clients\Endpoints\GetListEndpoint;
use Digiom\ApiMoySklad\Clients\Endpoints\PostEndpoint;
use Digiom\ApiMoySklad\Clients\Endpoints\PutByIdEndpoint;
use Digiom\ApiMoySklad\Entities\Discounts\AccumulationDiscount;

/**
 * Class AccumulationDiscountClient
 *
 * @package Digiom\ApiMoySklad\Clients
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
     * @param Client $api
     */
    public function __construct(Client $api)
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
