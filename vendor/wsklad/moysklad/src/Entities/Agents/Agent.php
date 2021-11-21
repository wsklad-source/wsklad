<?php
/**
 * Namespace
 */
namespace Wsklad\MoySklad\Entities\Agents;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Wsklad\MoySklad\Entities\Fetchable;
use Wsklad\MoySklad\Entities\MetaEntity;

/**
 * Class Agent
 * Сущность агентов
 *
 * @package Wsklad\MoySklad\Entities\Agents
 */
class Agent extends MetaEntity implements Fetchable
{
}
