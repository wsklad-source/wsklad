<?php namespace Wsklad\Extensions\Contracts;

defined('ABSPATH') || exit;

use Wsklad\Exceptions\Exception;
use Wsklad\Exceptions\RuntimeException;

/**
 * ExtensionContract
 *
 * @package Wsklad\Extenstions
 */
interface ExtensionContract
{
	/**
	 * Initializing
	 *
	 * @return void
	 * @throws Exception
	 */
	public function init();

	/**
	 * Initializing status
	 *
	 * @return boolean True for yes, false for no
	 */
	public function isInitialized();

	/**
	 * Setup initializing status
	 *
	 * @param boolean $initialized true for yes, false for no
	 */
	public function setInitialized($initialized);

	/**
	 * Get extension id
	 *
	 * @return string Extension id
	 */
	public function getId();

	/**
	 * Setup extension id
	 *
	 * @param string|integer $id Extension id
	 *
	 * @return ExtensionContract
	 */
	public function setId($id);

	/**
	 * Set meta information for extension
	 *
	 * @param string $name Meta name
	 * @param string $value Meta value
	 *
	 * @return ExtensionContract
	 */
	public function setMeta($name, $value = '');

	/**
	 * Get meta information for extension
	 *
	 * @param string $name Meta name
	 * @param string $default_value Default meta value
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	public function getMeta($name, $default_value = '');

	/**
	 * Load meta data by plugin file
	 *
	 * @param string $file Plugin file
	 * @param string $locale Locale, default - use plugin textdomain
	 *
	 * @return boolean
	 */
	public function loadMetaByPlugin($file, $locale = '');
}