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
 * Dependencies
 */
use RuntimeException;
use Wsklad\MoySklad\Clients\EntityClientBase;
use Wsklad\MoySklad\Clients\SettingsClient;

/**
 * Trait HasSettingsEndpoint
 *
 * @package Wsklad\MoySklad\Clients\Endpoints
 */
trait HasSettingsEndpoint
{
	/**
	 * @return SettingsClient
	 * @throws RuntimeException
	 */
	public function settings()
	{
		if(get_parent_class($this) !== EntityClientBase::class)
		{
			throw new RuntimeException('The trait cannot be used outside the EntityClientBase class');
		}

		return new SettingsClient($this->api(), $this->path(), $this->settingsEntityClass());
	}
}
