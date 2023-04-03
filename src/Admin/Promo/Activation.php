<?php namespace Wsklad\Admin\Promo;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Wsklad\Abstracts\ScreenAbstract;
use Wsklad\Admin\Traits\ProcessAccountTrait;
use function Wsklad\core;

/**
 * Activation
 *
 * @package Wsklad\Admin\Promo
 */
final class Activation
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
	 * Output
	 *
	 * @return void
	 */
	public function output()
	{
		$args['object'] = $this;

		core()->views()->getView('promo/activation.php', $args);
	}
}