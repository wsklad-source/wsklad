<?php
/**
 * Namespace
 */
namespace Wsklad\MoySklad\Utils\Http;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Class RequestDelete
 *
 * @package Wsklad\MoySklad\Utils\Http
 */
class RequestDelete extends Request
{
	const METHOD = 'DELETE';

	/**
	 * RequestDelete constructor.
	 *
	 * @param $uri
	 * @param array $headers
	 * @param null $body
	 * @param string $version
	 */
	public function __construct($uri, $headers = [], $body = null, $version = '1.1')
	{
		parent::__construct($this->getMethod(), $uri, $headers, $body, $version);
	}

	/**
	 * @return string
	 */
	public function getMethod()
	{
		return self::METHOD;
	}
}