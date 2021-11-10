<?php
/**
 * Namespace
 */
namespace Wsklad;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Digiom\WordPress\Admin\Notices\Interfaces\ManagerInterface;
use Digiom\WordPress\Admin\Notices\Manager;
use Wsklad\Admin\Accounts;
use Wsklad\Admin\Extensions;
use Wsklad\Admin\Help;
use Wsklad\Admin\Settings;
use Wsklad\Admin\Tools;
use Wsklad\Traits\Sections;
use Wsklad\Traits\Singleton;

/**
 * Class Admin
 *
 * @package Wsklad
 */
final class Admin
{
	use Singleton;
	use Sections;

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
		do_action(WSKLAD_ADMIN_PREFIX. 'before_loading');

		add_action('admin_menu', [$this, 'addMenu'], 30);

		if(is_wsklad_admin_request())
		{
			add_action('init', [$this, 'init'], 10);
			add_action('admin_enqueue_scripts', [$this, 'initStyles']);

			Help::instance();
		}

		if(defined('WSKLAD_PLUGIN_NAME'))
		{
			add_filter('plugin_action_links_' . WSKLAD_PLUGIN_NAME, [$this, 'linksLeft']);
		}

		// hook
		do_action(WSKLAD_ADMIN_PREFIX. 'after_loading');
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
				'admin_notices' => false,
				'user_admin_notices' => false,
				'network_admin_notices' => false
			];

			$this->notices = new Manager(WSKLAD_ADMIN_PREFIX . 'notices', $args);
		}

		return $this->notices;
	}

	/**
	 * Init menu
	 */
	public function addMenu()
	{
		add_submenu_page
		(
			'woocommerce',
			__('MoySklad', 'wsklad'),
			__('MoySklad', 'wsklad'),
			'manage_woocommerce',
			'wsklad', [$this, 'route']
		);
	}

	/**
	 * Initialization
	 */
	public function init()
	{
		// hook
		do_action(WSKLAD_ADMIN_PREFIX. 'before_init');

		$default_sections['accounts'] =
		[
			'title' => __('Accounts', 'wsklad'),
			'visible' => true,
			'callback' => [Accounts::class, 'instance']
		];

		$default_sections['tools'] =
		[
			'title' => __('Tools', 'wsklad'),
			'visible' => true,
			'callback' => [Tools::class, 'instance']
		];

		$default_sections['settings'] =
		[
			'title' => __('Settings', 'wsklad'),
			'visible' => true,
			'callback' => [Settings::class, 'instance']
		];

		$default_sections['extensions'] =
		[
			'title' => __('Extensions', 'wsklad'),
			'visible' => true,
			'callback' => [Extensions::class, 'instance']
		];

		$this->initSections($default_sections);
		$this->setCurrentSection('accounts');

		// hook
		do_action(WSKLAD_ADMIN_PREFIX. 'after_init');
	}

	/**
	 * Styles
	 */
	public function initStyles()
	{
		wp_enqueue_style(WSKLAD_ADMIN_PREFIX. 'main', WSKLAD_PLUGIN_URL . 'assets/css/main.css');
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
			add_action(WSKLAD_ADMIN_PREFIX . 'show', [$this, 'wrapError']);
		}
		else
		{
			add_action(WSKLAD_ADMIN_PREFIX . 'show', [$this, 'wrapHeader'], 3);
			add_action(WSKLAD_ADMIN_PREFIX . 'show', [$this, 'wrapSections'], 7);

			$callback = $sections[$current_section]['callback'];

			if(is_callable($callback, false, $callback_name))
			{
				$callback_name();
			}
		}

		wsklad_get_template('wrap.php');
	}

	/**
	 * Error
	 */
	public function wrapError()
	{
		wsklad_get_template('error.php');
	}

	/**
	 * Header
	 */
	public function wrapHeader()
	{
		wsklad_get_template('header.php');
	}

	/**
	 * Sections
	 */
	public function wrapSections()
	{
		wsklad_get_template('sections.php');
	}

	/**
	 * Setup left links
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public function linksLeft($links)
	{
		return array_merge(['site' => '<a href="' . admin_url('admin.php?page=wsklad') . '">' . __('Settings', 'wsklad') . '</a>'], $links);
	}
}