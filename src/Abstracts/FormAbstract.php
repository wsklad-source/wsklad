<?php namespace Wsklad\Abstracts;

defined('ABSPATH') || exit;

/**
 * Class FormAbstract
 *
 * @package Wsklad\Abstracts
 */
abstract class FormAbstract extends \Digiom\Woplucore\Abstracts\FormAbstract
{
	/**
	 * @var string Unique slug
	 */
    protected $prefix = 'wsklad';
}