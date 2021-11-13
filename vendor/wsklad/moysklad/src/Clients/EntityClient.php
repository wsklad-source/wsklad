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
use Wsklad\MoySklad\Clients\Documents\CustomerOrderClient;

/**
 * Class EntityClient
 *
 * @package Wsklad\MoySklad\Clients
 */
final class EntityClient
{
	/**
	 * @var ApiClient
	 */
	private $api;

	/**
	 * EntityClient constructor.
	 *
	 * @param ApiClient $api
	 */
	public function __construct($api)
	{
		$this->api = $api;
	}

	/**
	 * @return CounterpartyClient
	 */
	public function counterparty()
	{
		return new CounterpartyClient($this->api);
	}

	/**
	 * @return OrganizationClient
	 */
	public function organization()
	{
		return new OrganizationClient($this->api);
	}

	/**
	 * @return GroupClient
	 */
	public function group()
	{
		return new GroupClient($this->api);
	}

	/**
	 * @return EmployeeClient
	 */
	public function employee()
	{
		return new EmployeeClient($this->api);
	}

	/**
	 * @return CustomerOrderClient
	 */
	public function customerorder()
	{
		return new CustomerOrderClient($this->api);
	}

	/**
	 * @return DemandClient
	 */
	public function demand()
	{
		return new DemandClient($this->api);
	}

	/**
	 * @return EnterClient
	 */
	public function enter()
	{
		return new EnterClient($this->api);
	}

	/**
	 * @return InternalOrderClient
	 */
	public function internalorder()
	{
		return new InternalOrderClient($this->api);
	}

	/**
	 * @return LossClient
	 */
	public function loss()
	{
		return new LossClient($this->api);
	}

	/**
	 * @return MoveClient
	 */
	public function move()
	{
		return new MoveClient($this->api);
	}

	/**
	 * @return PurchaseOrderClient
	 */
	public function purchaseorder()
	{
		return new PurchaseOrderClient($this->api);
	}

	/**
	 * @return ProcessingOrderClient
	 */
	public function processingorder()
	{
		return new ProcessingOrderClient($this->api);
	}

	/**
	 * @return ProcessingPlanClient
	 */
	public function processingplan()
	{
		return new ProcessingPlanClient($this->api);
	}

	/**
	 * @return SalesReturnClient
	 */
	public function salesreturn()
	{
		return new SalesReturnClient($this->api);
	}

	/**
	 * @return PurchaseReturnClient
	 */
	public function purchasereturn()
	{
		return new PurchaseReturnClient($this->api);
	}

	/**
	 * @return SupplyClient
	 */
	public function supply()
	{
		return new SupplyClient($this->api);
	}

	/**
	 * @return StoreClient
	 */
	public function store()
	{
		return new StoreClient($this->api);
	}

	/**
	 * @return ProductClient
	 */
	public function product()
	{
		return new ProductClient($this->api);
	}

	/**
	 * @return VariantClient
	 */
	public function variant()
	{
		return new VariantClient($this->api);
	}

	/**
	 * @return CurrencyClient
	 */
	public function currency()
	{
		return new CurrencyClient($this->api);
	}

	/**
	 * @return DiscountClient
	 */
	public function discount()
	{
		return new DiscountClient($this->api);
	}

	/**
	 * @return ContractClient
	 */
	public function contract()
	{
		return new ContractClient($this->api);
	}

	/**
	 * @return ConsignmentClient
	 */
	public function consignment()
	{
		return new ConsignmentClient($this->api);
	}

	/**
	 * @return ProductFolderClient
	 */
	public function productfolder()
	{
		return new ProductFolderClient($this->api);
	}

	/**
	 * @return ServiceClient
	 */
	public function service()
	{
		return new ServiceClient($this->api);
	}

	/**
	 * @return UomClient
	 */
	public function uom()
	{
		return new UomClient($this->api);
	}

	/**
	 * @return CashInClient
	 */
	public function cashin()
	{
		return new CashInClient($this->api);
	}

	/**
	 * @return CashOutClient
	 */
	public function cashout()
	{
		return new CashOutClient($this->api);
	}

	/**
	 * @return RetailShiftClient
	 */
	public function retailshift()
	{
		return new RetailShiftClient($this->api);
	}

	/**
	 * @return RetailStoreClient
	 */
	public function retailstore()
	{
		return new RetailStoreClient($this->api);
	}

	/**
	 * @return RetailDemandClient
	 */
	public function retaildemand()
	{
		return new RetailDemandClient($this->api);
	}

	/**
	 * @return RetailSalesReturnClient
	 */
	public function retailsalesreturn()
	{
		return new RetailSalesReturnClient($this->api);
	}

	/**
	 * @return RetailDrawerCashInClient
	 */
	public function retaildrawercashin()
	{
		return new RetailDrawerCashInClient($this->api);
	}

	/**
	 * @return RetailDrawerCashOutClient
	 */
	public function retaildrawercashout()
	{
		return new RetailDrawerCashOutClient($this->api);
	}

	/**
	 * @return CommissionReportInClient
	 */
	public function commissionreportin()
	{
		return new CommissionReportInClient($this->api);
	}

	/**
	 * @return InvoiceInClient
	 */
	public function invoicein()
	{
		return new InvoiceInClient($this->api);
	}

	/**
	 * @return InvoiceOutClient
	 */
	public function invoiceout()
	{
		return new InvoiceOutClient($this->api);
	}

	/**
	 * @return InventoryClient
	 */
	public function inventory()
	{
		return new InventoryClient($this->api);
	}

	/**
	 * @return CommissionReportOutClient
	 */
	public function commissionreportout()
	{
		return new CommissionReportOutClient($this->api);
	}

	/**
	 * @return PaymentInClient
	 */
	public function paymentin()
	{
		return new PaymentInClient($this->api);
	}

	/**
	 * @return PaymentOutClient
	 */
	public function paymentout()
	{
		return new PaymentOutClient($this->api);
	}

	/**
	 * @return ProjectClient
	 */
	public function project()
	{
		return new ProjectClient($this->api);
	}

	/**
	 * @return ExpenseItemClient
	 */
	public function expenseitem()
	{
		return new ExpenseItemClient($this->api);
	}

	/**
	 * @return ProcessingClient
	 */
	public function processing()
	{
		return new ProcessingClient($this->api);
	}

	/**
	 * @return FactureInClient
	 */
	public function facturein()
	{
		return new FactureInClient($this->api);
	}

	/**
	 * @return FactureOutClient
	 */
	public function factureout()
	{
		return new FactureOutClient($this->api);
	}

	/**
	 * @return PricelistClient
	 */
	public function pricelist()
	{
		return new PricelistClient($this->api);
	}

	/**
	 * @return CustomEntityClient
	 */
	public function customentity()
	{
		return new CustomEntityClient($this->api);
	}

	/**
	 * @return CountryClient
	 */
	public function country()
	{
		return new CountryClient($this->api);
	}

	/**
	 * @return MetadataClient
	 */
	public function metadata()
	{
		return new MetadataClient($this->api);
	}

	/**
	 * @return CompanySettingsClient
	 */
	public function companysettings()
	{
		return new CompanySettingsClient($this->api);
	}

	/**
	 * @return TaskClient
	 */
	public function task()
	{
		return new TaskClient($this->api);
	}

	/**
	 * @return BonusProgramClient
	 */
	public function bonusprogram()
	{
		return new BonusProgramClient($this->api);
	}

	/**
	 * @return BonusTransactionClient
	 */
	public function bonustransaction()
	{
		return new BonusTransactionClient($this->api);
	}

	/**
	 * @return PersonalDiscountClient
	 */
	public function personaldiscount()
	{
		return new PersonalDiscountClient($this->api);
	}

	/**
	 * @return AccumulationDiscountClient
	 */
	public function accumulationdiscount()
	{
		return new AccumulationDiscountClient($this->api);
	}

	/**
	 * @return RoundOffDiscountClient
	 */
	public function roundoffdiscount()
	{
		return new RoundOffDiscountClient($this->api);
	}

	/**
	 * @return SpecialPriceDiscountClient
	 */
	public function specialpricediscount()
	{
		return new SpecialPriceDiscountClient($this->api);
	}

	/**
	 * @return RegionClient
	 */
	public function region()
	{
		return new RegionClient($this->api);
	}

	/**
	 * @return AssortmentClient
	 */
	public function assortment()
	{
		return new AssortmentClient($this->api);
	}

	/**
	 * @return WebHookClient
	 */
	public function webhook()
	{
		return new WebHookClient($this->api);
	}

	/**
	 * @return TokenClient
	 */
	public function token()
	{
		return new TokenClient($this->api);
	}

	/**
	 * @return PrepaymentClient
	 */
	public function prepayment()
	{
		return new PrepaymentClient($this->api);
	}

	/**
	 * @return PrepaymentReturnClient
	 */
	public function prepaymentReturn()
	{
		return new PrepaymentReturnClient($this->api);
	}
}
