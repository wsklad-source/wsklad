<?php namespace Digiom\ApiMoySklad\Clients;

use Digiom\ApiMoySklad\Client;
use Digiom\ApiMoySklad\Clients\Endpoints\{GetEndpoint, MetadataEndpoint, PutEndpoint};
use Digiom\ApiMoySklad\Entities\CompanySettings;

/**
 * Class CompanySettingsClient
 *
 * @package Digiom\ApiMoySklad\Clients
 */
final class CompanySettingsClient extends EntityClientBase
{
	use GetEndpoint,
		PutEndpoint,
		MetadataEndpoint;

	/**
	 * CompanySettingsClient constructor.
	 *
	 * @param Client $api
	 */
	public function __construct(Client $api)
	{
		parent::__construct($api, '/context/companysettings/');
	}

	/**
	 * @return PriceTypeClient
	 */
	public function pricetype(): PriceTypeClient
	{
		return new PriceTypeClient($this->api(), $this->path());
	}

	/**
	 * @return string
	 */
	public function entityClass(): string
	{
		return CompanySettings::class;
	}

	/**
	 * @return string
	 */
	public function metaEntityClass(): string
	{
		return CompanySettingsMetadata::class;
	}
}
