<?php
/**
 * Namespace
 */
namespace Wsklad\Exceptions;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use RuntimeException as SystemRuntimeException;

/**
 * RuntimeException
 *
 * @package Wsklad/Exceptions
 */
class RuntimeException extends SystemRuntimeException
{}