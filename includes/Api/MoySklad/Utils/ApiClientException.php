<?php
/**
 * Namespace
 */
namespace Wsklad\Api\MoySklad\Utils;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Exception;
use Wsklad\Api\MoySklad\Responses\ErrorResponse;

/**
 * Class ApiClientException
 *
 * @package Wsklad\Api\MoySklad\Utils
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
	 * @var ErrorResponse
	 */
	protected $errorResponse;

	/**
	 * ApiClientException constructor.
	 *
	 * @param string $uri
	 * @param int $statusCode
	 * @param string $reasonPhrase
	 */
	public function __construct($uri, $statusCode, $reasonPhrase, $er = null)
	{
		parent::__construct($uri . ': ' . $statusCode . ' ' . $reasonPhrase, $statusCode);

		$this->statusCode = $statusCode;
		$this->reasonPhrase = $reasonPhrase;

		if($er instanceof ErrorResponse)
		{
			$this->errorResponse = $er;
		}
	}
}
