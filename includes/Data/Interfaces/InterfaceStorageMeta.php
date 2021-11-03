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
 * Interface InterfaceStorageMeta
 *
 * @package Wsklad\Data\Interfaces
 */
interface InterfaceStorageMeta
{
	/**
	 * Returns an array of meta for an object
	 *
	 * @param Data $data Data object
	 *
	 * @return array
	 */
	public function read_meta(&$data);

	/**
	 * Deletes meta based on meta ID
	 *
	 * @param Data $data Data object
	 * @param object $meta Meta object (containing at least ->id)
	 *
	 * @return array
	 */
	public function delete_meta(&$data, $meta);

	/**
	 * Add new piece of meta.
	 *
	 * @param Data $data Data object
	 * @param object $meta Meta object (containing ->key and ->value)
	 *
	 * @return int meta ID
	 */
	public function add_meta(&$data, $meta);

	/**
	 * Update meta
	 *
	 * @param Data $data Data object
	 * @param object $meta Meta object (containing ->id, ->key and ->value)
	 */
	public function update_meta(&$data, $meta);
}