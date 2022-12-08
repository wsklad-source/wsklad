<?php namespace Digiom\ApiMoySklad\Entities;

/**
 * Class CompanySettings
 *
 * @package Digiom\ApiMoySklad\Entities
 */
class CompanySettings extends MetaEntity
{
	/**
	 * Ссылка на стандартную валюту
	 */
	private $currency;

    /**
     * Список типов цен
     */
    private $priceTypes;

    /**
     * Совместное применение скидок
     */
    private $discountStrategy;

	/**
	 * Использовать сквозную нумерацию документов
	 *
	 * @var bool
	 */
	private $globalOperationNumbering;

    /**
     * Запретить отгрузку отсутствующих товаров
     *
     * @var bool
     */
    private $checkShippingStock;

    /**
     * Автоматически устанавливать минимальную цену
     *
     * @var bool
     */
    private $checkMinPrice;

    /**
     * Использовать корзину
     *
     * @var bool
     */
    private $useRecycleBin;

    /**
     * Использовать адрес компании для электронных писем
     *
     * @var bool
     */
    private $useCompanyAddress;

    /**
     * Адрес компании для электронных писем
     *
     * @var string
     */
    private $companyAddress;
}
