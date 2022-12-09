<?php namespace Wsklad;

defined('ABSPATH') || exit;

use Digiom\Woap\Client;

/**
 * Connection
 *
 * @package Wsklad
 */
final class Connection extends Client
{
	/**
	 * @var string
	 */
	protected $host = 'https://wsklad.ru';
}