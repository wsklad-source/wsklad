<?php
/**
 * Namespace
 */
namespace Wsklad\MoySklad\Entities;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Class Rate
 * Валюта документа
 *
 * @package Wsklad\MoySklad\Entities
 */
class Rate
{
	/**
	 * @Type("Wsklad\MoySklad\Entities\Currency")
	 */
	public $currency;

	/**
	 * @Type("double")
	 */
	public $value;
}
