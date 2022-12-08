<?php namespace Digiom\ApiMoySklad\Clients;

use Digiom\ApiMoySklad\Client;
use Digiom\ApiMoySklad\Clients\Endpoints\{GetListEndpoint, HasSettingsEndpoint};
use Digiom\ApiMoySklad\Entities\Assortment;

/**
 * Class AssortmentClient
 *
 * @package Digiom\ApiMoySklad\Clients
 */
final class AssortmentClient extends EntityClientBase
{
	use GetListEndpoint,
		HasSettingsEndpoint;

	/**
	 * AssortmentClient constructor.
	 *
	 * @param Client $api
	 */
	public function __construct(Client $api)
	{
		parent::__construct($api, '/entity/assortment/');
	}

	public function entityClass(): string
	{
		return Assortment::class;
	}

	public function settingsEntityClass(): string
	{
		return AssortmentSettings::class;
	}
}
