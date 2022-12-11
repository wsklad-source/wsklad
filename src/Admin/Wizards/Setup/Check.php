<?php namespace Wsklad\Admin\Wizards\Setup;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Wsklad\Admin\Wizards\StepAbstract;

/**
 * Check
 *
 * @package Wsklad\Admin\Wizards
 */
class Check extends StepAbstract
{
	use SingletonTrait;

	/**
	 * Check constructor.
	 */
	public function __construct()
	{
		$this->setId('check');
	}

	/**
	 * Precessing step
	 */
	public function process()
	{
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
			'step' => $this
		];

		wsklad()->views()->getView('wizards/steps/check.php', $args);
	}
}