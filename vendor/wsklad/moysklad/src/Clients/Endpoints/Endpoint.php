<?php
/**
 * Namespace
 */
namespace Wsklad\MoySklad\Clients\Endpoints;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Wsklad\MoySklad\ApiClient;

/**
 * Interface Endpoint
 *
 * @package Wsklad\MoySklad\Clients\Endpoints
 */
interface Endpoint
{
	/**
	 * @return string
	 */
	public function path();

	/**
	 * @return ApiClient
	 */
	public function api();
}