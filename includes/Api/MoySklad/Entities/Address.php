<?php
/**
 * Namespace
 */
namespace Wsklad\Api\MoySklad\Entities;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Wsklad\Api\MoySklad\Utils\Object\Annotation\Generator;

/**
 * Class Address
 *
 * @package Wsklad\Api\MoySklad\Entities
 */
class Address
{
	/**
	 * @Type("string")
	 * @Generator()
	 */
	public $postalCode;

	/**
	 * @Type("Wsklad\Api\MoySklad\Entities\Country")
	 * @Generator(type="object")
	 */
	public $country;

	/**
	 * @Type("Wsklad\Api\MoySklad\Entities\Region")
	 * @Generator(type="object", anyFromExists=true)
	 */
	public $region;

	/**
	 * @Type("string")
	 * @Generator()
	 */
	public $city;

	/**
	 * @Type("string")
	 * @Generator()
	 */
	public $street;

	/**
	 * @Type("string")
	 * @Generator()
	 */
	public $house;

	/**
	 * @Type("string")
	 * @Generator()
	 */
	public $apartment;

	/**
	 * @Type("string")
	 * @Generator()
	 */
	public $addInfo;

	/**
	 * @Type("string")
	 * @Generator()
	 */
	public $comment;
}
