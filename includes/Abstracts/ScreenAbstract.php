<?php
/**
 * Namespace
 */
namespace Wsklad\Abstracts;

/**
 * Only WordPress
 */
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
		add_action(WSKLAD_ADMIN_PREFIX . 'show', [$this, 'output'], 10);
	}

	/**
	 * @return mixed
	 */
	abstract public function output();
}