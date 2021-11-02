<?php
/**
 * Namespace
 */
namespace Wsklad;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Wsklad\Traits\Singleton;

/**
 * Class Admin
 *
 * @package Wsklad
 */
class Admin
{
	/**
	 * Traits
	 */
	use Singleton;

	/**
	 * Admin messages
	 *
	 * @var array
	 */
	private $messages = [];

	/**
	 * Admin sections
	 *
	 * @var array
	 */
	private $sections = [];

	/**
	 * Current admin section
	 *
	 * @var string
	 */
	private $current_section = 'accounts';
}