<?php
/**
 * Namespace
 */
namespace Wsklad\MoySklad\Clients\Endpoints;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Interface ApiChainElement
 *
 * Техническая аннотация, ставящаяся над промежуточным звеном запроса (например в <code>entity().counterparty().get()</code>
 * метод <code>counterparty()</code> должен быть отмечен этой аннотацией)
 *
 * @package Wsklad\MoySklad\Clients\Endpoints
 */
interface ApiChainElement
{

}