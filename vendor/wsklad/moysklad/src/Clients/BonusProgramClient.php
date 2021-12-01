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
use Wsklad\MoySklad\Entities\Discounts\BonusProgram;

/**
 * Class BonusProgramClient
 *
 * @package Wsklad\MoySklad\Clients
 */
final class BonusProgramClient extends EntityClientBase
{
    use GetListEndpoint,
	    GetByIdEndpoint,
	    PostEndpoint,
	    PutByIdEndpoint,
	    DeleteByIdEndpoint;

    /**
     * BonusProgramClient constructor.
     *
     * @param ApiClient $api
     */
    public function __construct(ApiClient $api)
    {
        parent::__construct($api, '/entity/bonusprogram/');
    }

    /**
     * @return string
     */
    public function entityClass()
    {
        return BonusProgram::class;
    }
}
