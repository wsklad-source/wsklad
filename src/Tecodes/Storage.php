<?php namespace Wsklad\Tecodes;

defined('ABSPATH') || exit;

use Tecodes_Local_Storage_Code;

/**
 * Tecodes storage code class
 *
 * @package Wsklad\Tecodes
 */
class Storage extends Tecodes_Local_Storage_Code
{
	/**
	 * @var string
	 */
	protected $option_name = 'wsklad_tecodes_code';
}