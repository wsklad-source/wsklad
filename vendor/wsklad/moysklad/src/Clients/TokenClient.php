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
use Wsklad\MoySklad\Entities\Token;
use Wsklad\MoySklad\Utils\HttpRequestExecutor;

/**
 * Class TokenClient
 *
 * @package Wsklad\MoySklad\Clients
 */
class TokenClient extends EntityClientBase
{
    /**
     * TokenClient constructor.
     *
     * @param ApiClient $api
     */
    public function __construct(ApiClient $api)
    {
        parent::__construct($api, '/security/token');
    }

	public function create()
	{
		return HttpRequestExecutor::path($this->api(), $this->path())->body(null)->post($this->entityClass());
	}

	/**
	 * @return string
	 */
	public function entityClass()
	{
		return Token::class;
	}
}
