<?php namespace Wsklad\Admin\Accounts;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Wsklad\Abstracts\ScreenAbstract;

/**
 * Class Lists
 *
 * @package Wsklad\Admin\Accounts
 */
class All extends ScreenAbstract
{
	use SingletonTrait;

	/**
	 * Build and output table
	 */
	public function output()
	{
		$list_table = new AllTable();

		$args['object'] = $list_table;

		wsklad()->views()->getView('accounts/all.php', $args);
	}
}