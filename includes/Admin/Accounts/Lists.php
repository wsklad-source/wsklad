<?php
/**
 * Namespace
 */
namespace Wsklad\Admin\Accounts;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Wsklad\Abstracts\ScreenAbstract;
use Wsklad\Traits\Singleton;

/**
 * Class Lists
 *
 * @package Wsklad\Admin\Accounts
 */
class Lists extends ScreenAbstract
{
	use Singleton;

	/**
	 * Build and output table
	 */
	public function output()
	{
		$list_table = new ListsTable();
		$list_table->prepare_items();
		$list_table->display();
	}
}