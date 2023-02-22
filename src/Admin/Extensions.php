<?php namespace Wsklad\Admin;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Wsklad\Abstracts\ScreenAbstract;
use Wsklad\Admin\Extensions\All;
use Wsklad\Traits\SectionsTrait;

/**
 * Extensions
 *
 * @package Wsklad\Admin
 */
final class Extensions
{
	use SingletonTrait;
	use SectionsTrait;

	/**
	 * @var array Available actions
	 */
	private $actions =
	[
		'all',
	];

	/**
	 * @var string Current action
	 */
	private $current_action = 'all';

	/**
	 * Extensions constructor.
	 */
	public function __construct()
	{
		$default_sections['main'] =
		[
			'title' => __('Installed', 'wsklad'),
			'visible' => true,
			'callback' => [All::class, 'instance']
		];

		$sections = apply_filters('wsklad_admin_extensions_init_actions', $default_sections);

		$this->initSections($sections);
	}

	/**
	 * Initializing current section
	 *
	 * @return string
	 */
	public function initCurrentSection(): string
	{
		$current_section = !empty($_GET['do_extensions']) ? sanitize_key($_GET['do_extensions']) : 'main';

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
		wsklad()->views()->getView('extensions/sections.php');
	}

	/**
	 * Error
	 */
	public function wrapError()
	{
		wsklad()->views()->getView('extensions/error.php');
	}

	/**
	 * Header
	 */
	public function wrapHeader()
	{
		wsklad()->views()->getView('extensions/header.php');
	}
}