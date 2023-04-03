<?php namespace Wsklad\Admin\Promo;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Wsklad\Admin\Traits\ProcessAccountTrait;
use function Wsklad\core;

/**
 * Logs
 *
 * @package Wsklad\Admin\Promo
 */
final class Logs
{
	use SingletonTrait;
    use ProcessAccountTrait;

	/**
	 * Initialized
	 */
	public function process()
	{
        add_action('wsklad_admin_accounts_sections_single_show', [$this, 'output'], 10);
	}

	/**
	 * Output tools table
	 *
	 * @return void
	 */
	public function output()
	{
		$args['object'] = $this;

		core()->views()->getView('promo/logs.php', $args);
	}
}