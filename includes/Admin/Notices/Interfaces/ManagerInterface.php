<?php
/**
 * Namespace
 */
namespace Wsklad\Admin\Notices\Interfaces;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Interface StorageInterface
 *
 * @package Wsklad\Admin\Notices\Interfaces
 */
interface ManagerInterface
{
	/**
	 * Adding single notices
	 *
	 * @param $notice
	 * @param string $scope
	 * @param array $args
	 *
	 * @return boolean
	 */
	public function add($notice, $scope, $args);

	/**
	 * Get - all or single
	 *
	 * @param string $notice_type
	 * @param string $scope
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function get($notice_type, $scope, $args);

	/**
	 * Cleaning notices
	 *
	 * @param string $notice_type
	 * @param string $scope
	 *
	 * @return mixed
	 */
	public function purge($notice_type, $scope);

	/**
	 * Create single notices
	 *
	 * @param $notice_type
	 * @param string $title
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function create($notice_type, $title, $args);

	/**
	 * Output notices
	 *
	 * @param string $notice_type
	 * @param string $scope
	 *
	 * @return mixed
	 */
	public function output($notice_type, $scope);
}