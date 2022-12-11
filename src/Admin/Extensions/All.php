<?php namespace Wsklad\Admin\Extensions;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Wsklad\Abstracts\ScreenAbstract;

/**
 * All
 *
 * @package Wc1c\Admin\Extensions
 */
class All extends ScreenAbstract
{
	use SingletonTrait;

	/**
	 * Build and output table
	 */
	public function output()
	{
		$extensions = wsklad()->extensions()->get();

		if(empty($extensions))
		{
			wsklad()->views()->getView('extensions/empty.php');
			return;
		}

		$args['extensions'] = $extensions;

		wsklad()->views()->getView('extensions/all.php', $args);
	}
}