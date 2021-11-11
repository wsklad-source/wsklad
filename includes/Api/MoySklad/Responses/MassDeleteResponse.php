<?php
/**
 * Namespace
 */
namespace Wsklad\Api\MoySklad\Responses;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Class MassDeleteResponse
 *
 * @package Wsklad\Api\MoySklad\Responses
 */
class MassDeleteResponse
{
	/**
	 * @var string
	 */
	private $info;

	/**
	 * @var array
	 */
    private $errors;

	/**
	 * @return string
	 */
	public function getInfo()
	{
		return $this->info;
	}

	/**
	 * @param string $info
	 */
	public function setInfo($info)
	{
		$this->info = $info;
	}

	/**
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * @param array $errors
	 */
	public function setErrors($errors)
	{
		$this->errors = $errors;
	}
}