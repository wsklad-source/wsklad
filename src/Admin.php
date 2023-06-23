<?php namespace Wsklad;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Digiom\Wotices\Interfaces\ManagerInterface;
use Digiom\Wotices\Manager;
use Wsklad\Admin\Accounts;
use Wsklad\Admin\Add;
use Wsklad\Admin\Extensions;
use Wsklad\Admin\Settings;
use Wsklad\Admin\Tools;
use Wsklad\Traits\SectionsTrait;
use Wsklad\Traits\UtilityTrait;

/**
 * Class Admin
 *
 * @package Wsklad
 */
final class Admin
{
	use SingletonTrait;
	use SectionsTrait;
	use UtilityTrait;

	/**
	 * Admin notices
	 *
	 * @var ManagerInterface
	 */
	private $notices;

	/**
	 * Admin constructor.
	 */
	public function __construct()
	{
		// hook
		do_action('wsklad_admin_before_loading');

		$this->notices();

		add_action('admin_menu', [$this, 'addMenu'], 30);

		if(wsklad()->context()->isPluginAdmin())
		{
			add_action('admin_init', [$this, 'init'], 10);
			add_action('admin_enqueue_scripts', [$this, 'initStyles']);
			add_action('admin_enqueue_scripts', [$this, 'initScripts']);

			Admin\Helps\Init::instance();
			Admin\Wizards\Init::instance();
		}

		add_filter('plugin_action_links_' . wsklad()->environment()->get('plugin_basename'), [$this, 'linksLeft']);

		// hook
		do_action('wsklad_admin_after_loading');
	}

	/**
	 * Admin notices
	 *
	 * @return ManagerInterface
	 */
	public function notices()
	{
		if(empty($this->notices))
		{
			$args =
			[
				'auto_save' => true,
				'admin_notices' => !wsklad()->context()->isPluginAdmin(),
				'user_admin_notices' => false,
				'network_admin_notices' => false
			];

			$this->notices = new Manager('wsklad_admin_notices', $args);
		}

		return $this->notices;
	}

	/**
	 * Init menu
	 */
	public function addMenu()
	{
		$icon_data_uri = wsklad()->environment()->get('plugin_directory_url') . 'assets/images/menu-icon.png';

		add_menu_page
		(
			__('Moy Sklad', 'wsklad'),
			__('Moy Sklad', 'wsklad'),
			'manage_options',
			'wsklad',
			[$this, 'route'],
			$icon_data_uri,
			30
		);

		if(get_option('wsklad_wizard', false))
		{
			return;
		}

		add_submenu_page
		(
			'wsklad',
			__('Add accounts', 'wsklad'),
			__('Add accounts', 'wsklad'),
			'manage_options',
			'wsklad_add',
			[Add::instance(), 'route']
		);

		add_submenu_page
		(
			'wsklad',
			__('Tools', 'wsklad'),
			__('Tools', 'wsklad'),
			'manage_options',
			'wsklad_tools',
			[Tools::instance(), 'route']
		);

		add_submenu_page
		(
			'wsklad',
			__('Settings', 'wsklad'),
			__('Settings', 'wsklad'),
			'manage_options',
			'wsklad_settings',
			[Settings::instance(), 'route']
		);

		add_submenu_page
		(
			'wsklad',
			__('Extensions', 'wsklad'),
			__('Extensions', 'wsklad'),
			'manage_options',
			'wsklad_extensions',
			[Extensions::instance(), 'route']
		);
	}

	/**
	 * Initialization
	 */
	public function init()
	{
		// hook
		do_action('wsklad_admin_before_init');

		$default_sections['accounts'] =
		[
			'title' => __('Accounts', 'wsklad'),
			'visible' => true,
			'callback' => [Accounts::class, 'instance']
		];

		$this->initSections($default_sections);
		$this->setCurrentSection('accounts');

		// hook
		do_action('wsklad_admin_after_init');
	}

	/**
	 * Styles
	 */
	public function initStyles()
	{
		wp_enqueue_style
        (
            'wsklad_admin_main',
            wsklad()->environment()->get('plugin_directory_url') . 'assets/css/main.css',
            [],
            wsklad()->environment()->get('wsklad_version')
        );
	}

	/**
	 * Scripts
	 */
	public function initScripts()
	{
		wp_enqueue_script
        (
            'wsklad_admin_bootstrap',
            wsklad()->environment()->get('plugin_directory_url') . 'assets/js/bootstrap.bundle.min.js',
            [],
            wsklad()->environment()->get('wsklad_version')
        );
		wp_enqueue_script
        (
            'wsklad_admin_tocbot',
            wsklad()->environment()->get('plugin_directory_url') . 'assets/js/tocbot/tocbot.min.js',
            [],
            wsklad()->environment()->get('wsklad_version')
        );
		wp_enqueue_script
        (
            'wsklad_admin_main',
            wsklad()->environment()->get('plugin_directory_url') . 'assets/js/admin.js',
            [],
            wsklad()->environment()->get('wsklad_version')
        );
	}

	/**
	 * Route sections
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
			add_action( 'wsklad_admin_header_show', [$this, 'wrapHeader'], 3);

			$callback = $sections[$current_section]['callback'];

			if(is_callable($callback, false, $callback_name))
			{
				$callback_name();
			}
		}

		wsklad()->views()->getView('wrap.php');
	}

	/**
	 * Error
	 */
	public function wrapError()
	{
		wsklad()->views()->getView('error.php');
	}

	/**
	 * Header
	 */
	public function wrapHeader()
	{
		if(get_option('wsklad_wizard', false))
		{
			return;
		}

		$args['admin'] = $this;

		wsklad()->views()->getView('header.php', $args);
	}

	/**
	 * Setup left links
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public function linksLeft($links): array
	{
		return array_merge(['site' => '<a href="' . admin_url('admin.php?page=wsklad') . '">' . __('Dashboard', 'wsklad') . '</a>'], $links);
	}
}