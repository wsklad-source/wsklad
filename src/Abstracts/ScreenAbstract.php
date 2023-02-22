<?php namespace Wsklad\Abstracts;

defined('ABSPATH') || exit;

/**
 * Class ScreenAbstract
 *
 * @package Wsklad\Abstracts
 */
abstract class ScreenAbstract
{
	/**
	 * ScreenAbstract constructor.
	 */
	public function __construct()
	{
		add_action('wsklad_admin_show', [$this, 'output'], 10);
	}

	/**
	 * @return mixed
	 */
	abstract public function output();
}