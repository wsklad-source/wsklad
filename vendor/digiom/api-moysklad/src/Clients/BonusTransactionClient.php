<?php namespace Digiom\ApiMoySklad\Clients;

use Digiom\ApiMoySklad\Client;
use Digiom\ApiMoySklad\Clients\Endpoints\DeleteByIdEndpoint;
use Digiom\ApiMoySklad\Clients\Endpoints\GetByIdEndpoint;
use Digiom\ApiMoySklad\Clients\Endpoints\GetListEndpoint;
use Digiom\ApiMoySklad\Clients\Endpoints\MassCreateUpdateDeleteEndpoint;
use Digiom\ApiMoySklad\Clients\Endpoints\MetadataAttributeEndpoint;
use Digiom\ApiMoySklad\Clients\Endpoints\MetadataEndpoint;
use Digiom\ApiMoySklad\Clients\Endpoints\PostEndpoint;
use Digiom\ApiMoySklad\Clients\Endpoints\PutByIdEndpoint;
use Digiom\ApiMoySklad\Entities\BonusTransaction;

/**
 * Class BonusTransactionClient
 *
 * @package Digiom\ApiMoySklad\Clients
 */
final class BonusTransactionClient extends EntityClientBase
{
    use GetListEndpoint,
        PostEndpoint,
        MetadataEndpoint,
        MetadataAttributeEndpoint,
        GetByIdEndpoint,
        PutByIdEndpoint,
        MassCreateUpdateDeleteEndpoint,
        DeleteByIdEndpoint;

    /**
     * BonusTransactionClient constructor.
     *
     * @param Client $api
     */
    public function __construct(Client $api)
    {
        parent::__construct($api, '/entity/bonustransaction/');
    }

    /**
     * @return string
     */
    public function entityClass()
    {
        return BonusTransaction::class;
    }

	/**
	 * @return string
	 */
	public function metaEntityClass()
	{
		return MetadataAttributeSharedResponse::class;
	}
}
