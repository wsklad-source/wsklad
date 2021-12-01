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
use Wsklad\MoySklad\Clients\Endpoints\GetListEndpoint;
use Wsklad\MoySklad\Entities\Region;

/**
 * Class RegionClient
 *
 * @package Wsklad\MoySklad\Clients
 */
class RegionClient extends EntityClientBase
{
    use GetListEndpoint,
        GetByIdEndpoint;

	/**
	 * RegionClient constructor.
	 *
	 * @param ApiClient $api
	 */
    public function __construct(ApiClient $api)
    {
        parent::__construct($api, '/entity/region/');
    }

    /**
     * @return string
     */
    public function entityClass()
    {
        return Region::class;
    }
}
