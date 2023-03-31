<?php namespace Wsklad;

defined('ABSPATH') || exit;

/**
 * Context
 *
 * @package Wsklad
 */
final class Context extends \Digiom\Woplucore\Context
{
	/**
	 * @return bool
	 */
	public function isPluginAdmin(): bool
	{
		if(!isset($_GET['page']) || false === $this->isAdmin())
		{
			return false;
		}

		$pos = strrpos(sanitize_key($_GET['page']), $this->getSlug());

		return $pos !== false;
	}
}