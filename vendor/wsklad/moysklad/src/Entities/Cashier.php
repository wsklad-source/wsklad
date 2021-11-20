<?php
/**
 * Namespace
 */
namespace Wsklad\MoySklad\Entities;

/**
 * Class Cashier
 *
 * @package Wsklad\MoySklad\Entities
 */
class Cashier extends MetaEntity
{
	/**
	 * @Type("Wsklad\MoySklad\Entities\Agent\Employee")
	 */
	public $employee;

	/**
	 * @Type("Wsklad\MoySklad\Entities\Store\RetailStore")
	 */
	public $retailStore;
}
