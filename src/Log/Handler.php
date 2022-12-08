<?php namespace Wsklad\Log;

defined('ABSPATH') || exit;

use Monolog\Handler\RotatingFileHandler;

/**
 * Handler
 *
 * @package Wsklad
 */
final class Handler extends RotatingFileHandler
{
}