<?php namespace Digiom\ApiMoySklad\Utils;

use Exception;
use Digiom\ApiMoySklad\Responses\ErrorResponse;

/**
 * Class ClientException
 *
 * @package Digiom\ApiMoySklad\Utils
 */
class ClientException extends Exception
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
	 * ClientException constructor.
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
