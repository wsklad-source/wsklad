<?php namespace Wsklad\Admin;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Wsklad\Admin\Settings\ActivationForm;
use Wsklad\Admin\Settings\ConnectionForm;
use Wsklad\Admin\Settings\InterfaceForm;
use Wsklad\Admin\Settings\LogsForm;
use Wsklad\Admin\Settings\MainForm;
use Wsklad\Traits\SectionsTrait;

/**
 * Class Settings
 *
 * @package Wsklad\Admin
 */
class Settings
{
	use SingletonTrait;
	use SectionsTrait;

	/**
	 * Settings constructor.
	 */
	public function __construct()
	{
		// hook
		do_action('wsklad_admin_settings_before_loading');

		$this->init();

		// hook
		do_action('wsklad_admin_settings_after_loading');
	}

	/**
	 * Initialization
	 */
	public function init()
	{
		// hook
		do_action('wsklad_admin_settings_before_init');

		$default_sections['main'] =
		[
			'title' => __('Main', 'wsklad'),
			'visible' => true,
			'callback' => [MainForm::class, 'instance']
		];

		$default_sections['activation'] =
		[
			'title' => __('Activation', 'wsklad'),
			'visible' => true,
			'callback' => [ActivationForm::class, 'instance']
		];

		$default_sections['logs'] =
		[
			'title' => __('Event logs', 'wsklad'),
			'visible' => true,
			'callback' => [LogsForm::class, 'instance']
		];

		$default_sections['interface'] =
		[
			'title' => __('Interface', 'wsklad'),
			'visible' => true,
			'callback' => [InterfaceForm::class, 'instance']
		];

		$default_sections['connection'] =
		[
			'title' => __('Connection to the WSKLAD', 'wsklad'),
			'visible' => true,
			'callback' => [ConnectionForm::class, 'instance']
		];

		$this->initSections($default_sections);

		// hook
		do_action('wsklad_admin_settings_after_init');
	}

	/**
	 * Initializing current section
	 *
	 * @return string
	 */
	public function initCurrentSection(): string
	{
		$current_section = !empty($_GET['do_settings']) ? sanitize_title($_GET['do_settings']) : 'main';

		if($current_section !== '')
		{
			$this->setCurrentSection($current_section);
		}

		return $this->getCurrentSection();
	}

	/**
	 *  Routing
	 */
	public function route()
	{
		$sections = $this->getSections();
		$current_section = $this->initCurrentSection();

		if(!array_key_exists($current_section, $sections) || !isset($sections[$current_section]['callback']))
		{
			add_action('wsklad_admin_show', [$this, 'wrapError']);
		}
		else
		{
			add_action('wsklad_admin_header_show', [$this, 'wrapHeader'], 3);
			add_action('wsklad_admin_show', [$this, 'wrapSections'], 7);

			$callback = $sections[$current_section]['callback'];

			if(is_callable($callback, false, $callback_name))
			{
				$callback_name();
			}
		}

		wsklad()->views()->getView('wrap.php');
	}

	/**
	 * Sections
	 */
	public function wrapSections()
	{
		wsklad()->views()->getView('settings/sections.php');
	}

	/**
	 * Error
	 */
	public function wrapError()
	{
		wsklad()->views()->getView('settings/error.php');
	}

	/**
	 * Header
	 */
	public function wrapHeader()
	{
		wsklad()->views()->getView('settings/header.php');
	}
}