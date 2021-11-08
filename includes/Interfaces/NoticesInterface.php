<?php
/**
 * Namespace
 */
namespace Wsklad\Interfaces;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Interface NoticesInterface
 *
 * @package Wsklad\Interfaces
 */
interface NoticesInterface
{
	/**
	 * Get - all or single and by storage type
	 *
	 * @param string $notice_type
	 * @param string $storage_type
	 *
	 * @return mixed
	 */
	public function get($notice_type = 'all', $storage_type = 'none');

	/**
	 * Adding single notices
	 *
	 * @param $notice_type
	 * @param string $message
	 * @param array $args
	 * [
	 *  storage - user, global, none
	 * ]
	 *
	 * @return mixed
	 */
	public function add($notice_type, $message = '', $args = []);

	/**
	 * Output all notices
	 *
	 * @return mixed
	 */
	public function output();

	/**
	 * Cleaning all notices from permanent storages
	 *
	 * @return mixed
	 */
	public function purge();
}