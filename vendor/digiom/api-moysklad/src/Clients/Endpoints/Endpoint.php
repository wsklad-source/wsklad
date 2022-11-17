<?php namespace Digiom\ApiMoySklad\Clients\Endpoints;

use Digiom\ApiMoySklad\Client;

/**
 * Interface Endpoint
 *
 * @package Digiom\ApiMoySklad\Clients\Endpoints
 */
interface Endpoint
{
	/**
	 * @return string
	 */
	public function path();

	/**
	 * @return Client
	 */
	public function api();
}