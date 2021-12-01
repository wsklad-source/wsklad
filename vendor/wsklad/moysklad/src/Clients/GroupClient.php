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
use Wsklad\MoySklad\Entities\Group;

/**
 * Class GroupClient
 *
 * @package Wsklad\MoySklad\Clients
 */
class GroupClient extends EntityClientBase
{
    use GetListEndpoint,
	    GetByIdEndpoint,
	    PostEndpoint,
	    PutByIdEndpoint,
	    DeleteByIdEndpoint;

    /**
     * GroupClient constructor.
     *
     * @param ApiClient $api
     */
    public function __construct(ApiClient $api)
    {
        parent::__construct($api, '/entity/group/');
    }

    /**
     * @return string
     */
    public function entityClass()
    {
        return Group::class;
    }
}
