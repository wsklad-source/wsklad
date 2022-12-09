<?php namespace Wsklad\Admin\Accounts;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Wsklad\Abstracts\ScreenAbstract;

/**
 * Class Lists
 *
 * @package Wsklad\Admin\Accounts
 */
class Lists extends ScreenAbstract
{
	use SingletonTrait;

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