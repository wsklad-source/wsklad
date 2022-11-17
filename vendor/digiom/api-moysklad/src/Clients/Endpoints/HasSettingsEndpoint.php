<?php namespace Digiom\ApiMoySklad\Clients\Endpoints;

use RuntimeException;
use Digiom\ApiMoySklad\Clients\EntityClientBase;
use Digiom\ApiMoySklad\Clients\SettingsClient;

/**
 * Trait HasSettingsEndpoint
 *
 * @package Digiom\ApiMoySklad\Clients\Endpoints
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
