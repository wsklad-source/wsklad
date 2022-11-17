<?php namespace Digiom\ApiMoySklad\Clients;

use Digiom\ApiMoySklad\Client;
use Digiom\ApiMoySklad\Clients\Endpoints\GetListEndpoint;
use Digiom\ApiMoySklad\Clients\Endpoints\HasSettingsEndpoint;
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

	public function entityClass()
	{
		return Assortment::class;
	}

	public function settingsEntityClass()
	{
		return AssortmentSettings::class;
	}
}
