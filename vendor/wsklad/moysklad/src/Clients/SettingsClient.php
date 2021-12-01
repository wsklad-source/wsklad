<?php
/**
 * Namespace
 */
namespace Wsklad\MoySklad\Clients;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Wsklad\MoySklad\ApiClient;
use Wsklad\MoySklad\Clients\Endpoints\GetEndpoint;
use Wsklad\MoySklad\Clients\Endpoints\PutEndpoint;

/**
 * Class SettingsClient
 *
 * @package Wsklad\MoySklad\Clients
 */
class SettingsClient extends EntityClientBase
{
    use GetEndpoint,
        PutEndpoint;

	/**
	 * @var string
	 */
	private $settingsClass;

	/**
	 * SettingsClient constructor.
	 *
	 * @param ApiClient $api
	 * @param $path
	 * @param $settingsClass
	 */
    public function __construct(ApiClient $api, $path, $settingsClass)
    {
        parent::__construct($api, $path . 'settings/');
		$this->settingsClass = $settingsClass;
    }

    /**
     * @return string
     */
    public function entityClass()
    {
        return $this->settingsClass;
    }
}
