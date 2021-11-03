<?php
/**
 * Namespace
 */
namespace Wsklad\Data\Interfaces;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Wsklad\Abstracts\Data;

/**
 * Interface InterfaceStorage
 *
 * @package Wsklad\Data\Interfaces
 */
interface InterfaceStorage
{
	/**
	 * Method to create a new record of a Data based object
	 *
	 * @param Data $data Data object
	 */
	public function create(&$data);

	/**
	 * Method to read a record.
	 *
	 * @param Data $data Data object
	 */
	public function read(&$data);

	/**
	 * Updates a record in the database
	 *
	 * @param Data $data Data object
	 */
	public function update(&$data);

	/**
	 * Deletes a record from the database
	 *
	 * @param Data $data Data object
	 * @param array $args Array of args to pass to the delete method
	 *
	 * @return bool result
	 */
	public function delete(&$data, $args = []);
}