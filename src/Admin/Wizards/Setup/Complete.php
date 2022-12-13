<?php namespace Wsklad\Admin\Wizards\Setup;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Wsklad\Admin\Wizards\StepAbstract;
use Wsklad\Traits\UtilityTrait;

/**
 * Complete
 *
 * @package Wsklad\Admin\Wizards
 */
class Complete extends StepAbstract
{
	use SingletonTrait;
	use UtilityTrait;

	/**
	 * Complete constructor.
	 */
	public function __construct()
	{
		$this->setId('complete');
	}

	/**
	 * Precessing step
	 */
	public function process()
	{
		delete_option('wsklad_wizard');
		update_option('wsklad_version', wsklad()->environment()->get('wsklad_version'));

		add_action('wsklad_wizard_content_output', [$this, 'output'], 10);
	}

	/**
	 * Output wizard content
	 *
	 * @return void
	 */
	public function output()
	{
		$args =
		[
			'step' => $this,
			'back_url' => $this->utilityAdminAccountsGetUrl('all'),
		];

		wsklad()->views()->getView('wizards/steps/complete.php', $args);
	}
}