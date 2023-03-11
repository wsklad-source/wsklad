<?php namespace Wsklad\Admin\Wizards;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Wsklad\Admin\Wizards\Setup\Check;
use Wsklad\Admin\Wizards\Setup\Complete;
use Wsklad\Admin\Wizards\Setup\Database;
use Wsklad\Exceptions\Exception;

/**
 * SetupWizard
 *
 * @package Wsklad\Admin\Wizards
 */
final class SetupWizard extends WizardAbstract
{
	use SingletonTrait;

	/**
	 * SetupWizard constructor.
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->setId('setup');
		$this->setDefaultSteps();
		$this->setStep(isset($_GET[$this->getId()]) ? sanitize_key($_GET[$this->getId()]) : current(array_keys($this->getSteps())));

		$this->init();
	}

	/**
	 * Initialize
	 */
	public function init()
	{
		add_filter('wsklad_admin_init_sections', [$this, 'hideSections'], 20, 1);
		add_filter('wsklad_admin_init_sections_current', [$this, 'setSectionsCurrent'], 20, 1);
		add_action('wsklad_admin_header_show', [$this, 'wrapHeader'], 3);
		add_action('wsklad_admin_show', [$this, 'route']);
	}

	/**
	 * Header
	 */
	public function wrapHeader()
	{
		wsklad()->views()->getView('wizards/header.php');
	}

	/**
	 * @param $sections
	 *
	 * @return array
	 */
	public function hideSections($sections)
	{
		$default_sections[$this->getId()] =
		[
			'title' => __('Setup wizard', 'wsklad'),
			'visible' => true,
			'callback' => [__CLASS__, 'instance']
		];

		return $default_sections;
	}

	/**
	 * @param $section
	 *
	 * @return string
	 */
	public function setSectionsCurrent($section)
	{
		return $this->getId();
	}

	/**
	 * @return void
	 */
	private function setDefaultSteps()
	{
		$default_steps =
		[
			'check' =>
			[
				'name' => __('Compatibility', 'wsklad'),
				'callback' => [Check::class, 'instance'],
			],
			'database' =>
			[
				'name' => __('Database', 'wsklad'),
				'callback' => [Database::class, 'instance'],
			],
			'complete' =>
			[
				'name' => __('Completing', 'wsklad'),
				'callback' => [Complete::class, 'instance'],
			],
		];

		$this->setSteps($default_steps);
	}
}