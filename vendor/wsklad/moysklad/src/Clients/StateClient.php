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
use Wsklad\MoySklad\Clients\Endpoints\MassCreateUpdateEndpoint;
use Wsklad\MoySklad\Clients\Endpoints\PostEndpoint;
use Wsklad\MoySklad\Clients\Endpoints\PutByIdEndpoint;
use Wsklad\MoySklad\Entities\State;

/**
 * Class StateClient
 *
 * @package Wsklad\MoySklad\Clients
 */
class StateClient extends EntityClientBase
{
    use GetByIdEndpoint,
        PostEndpoint,
        PutByIdEndpoint,
        MassCreateUpdateEndpoint,
        DeleteByIdEndpoint;

	/**
	 * StateClient constructor.
	 *
	 * @param ApiClient $api
	 * @param $path
	 */
    public function __construct(ApiClient $api, $path)
    {
        parent::__construct($api, $path . 'metadata/states/');
    }

    /**
     * @return string
     */
    public function entityClass()
    {
        return State::class;
    }
}
