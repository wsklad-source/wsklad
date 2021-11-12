<?php
/**
 * Namespace
 */
namespace Wsklad\Api\MoySklad\Utils\Http\Exceptions;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use InvalidArgumentException;

/**
 * Class MalformedUriException
 * Exception thrown if a URI cannot be parsed because it's malformed.
 *
 * @package Wsklad\Api\MoySklad\Utils\Http
 */
class MalformedUriException extends InvalidArgumentException
{
}
