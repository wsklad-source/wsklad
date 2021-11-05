<?php
/**
 * Namespace
 */
namespace Wsklad\Api\MoySklad\Exceptions;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Exception;

/**
 * Class ApiClientException
 *
 * @package Wsklad\Api\MoySklad\Exceptions
 */
class ApiClientException extends Exception
{
	/**
	 * @var int
	 */
	protected $statusCode;

	/**
	 * @var string
	 */
	protected $reasonPhrase;

	/**
	 * ApiClientException constructor.
	 *
	 * @param string $uri
	 * @param int $statusCode
	 * @param string $reasonPhrase
	 */
	public function __construct($uri, $statusCode, $reasonPhrase)
	{
		parent::__construct($uri . ': ' . $statusCode.' ' . $reasonPhrase, $statusCode);

		$this->statusCode = $statusCode;
		$this->reasonPhrase = $reasonPhrase;
	}
}
