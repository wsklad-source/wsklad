<?php namespace Wsklad\Data\Abstracts;

defined('ABSPATH') || exit;

/**
 * DataAbstract - Implemented by classes using the same CRUD(s) pattern
 *
 * The datetime helper is mixed in here rather than called through the storage, because
 * every entity in the plugin needs to read the same `VARCHAR` date columns and the
 * "unparseable means null, not the epoch" rule has to apply to all of them. Putting it
 * in the base is what makes it impossible for a new entity to reintroduce the bug.
 *
 * ⚠ Only two methods are imported, not the whole trait. The parent class already
 * defines `utilityTimezoneOffset()`, and importing the trait wholesale means a name
 * collision whose resolution depends on PHP's precedence rules rather than on anything
 * visible in this file. A selective import says exactly what is being borrowed.
 *
 * @package Wsklad\Data\Abstracts
 */
abstract class DataAbstract extends \Digiom\Woplucore\Data\Abstracts\DataAbstract
{
	/**
	 * ⚠ Only the date parser is borrowed, not the whole datetime helper: the parent
	 * class already defines `utilityTimezoneOffset()`, and a trait imported wholesale
	 * collides on that name. A dedicated trait says exactly what is being taken.
	 */
	use \Wsklad\Traits\StoredDateTrait;
}