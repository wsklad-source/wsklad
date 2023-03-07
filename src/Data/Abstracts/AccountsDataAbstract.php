<?php namespace Wsklad\Data\Abstracts;

defined('ABSPATH') || exit;

/**
 * AccountsDataAbstract
 *
 * @package Wsklad\Data\Abstracts
 */
abstract class AccountsDataAbstract extends WithMetaDataAbstract
{
	/**
	 * This is the name of this object type
	 *
	 * @var string
	 */
	protected $object_type = 'account';
}