<?php namespace Wsklad;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Digiom\Wotices\Interfaces\ManagerInterface;
use Digiom\Wotices\Manager;
use Wsklad\Admin\Accounts;
use Wsklad\Admin\Settings;
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

		if(wsklad()->context()->isAdmin('plugin'))
		{
			add_action('admin_init', [$this, 'init'], 10);
			add_action('admin_enqueue_scripts', [$this, 'initStyles']);

			Admin\Helps\Init::instance();
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
				'admin_notices' => false,
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
		$icon_data_uri = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+CjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiIHdpZHRoPSIxNDJweCIgaGVpZ2h0PSIxMTJweCIgdmlld0JveD0iMCAwIDE0MiAxMTIiIGVuYWJsZS1iYWNrZ3JvdW5kPSJuZXcgMCAwIDE0MiAxMTIiIHhtbDpzcGFjZT0icHJlc2VydmUiPiAgPGltYWdlIGlkPSJpbWFnZTAiIHdpZHRoPSIxNDIiIGhlaWdodD0iMTEyIiB4PSIwIiB5PSIwIgogICAgaHJlZj0iZGF0YTppbWFnZS9wbmc7YmFzZTY0LGlWQk9SdzBLR2dvQUFBQU5TVWhFVWdBQUFJNEFBQUJ3Q0FNQUFBRC91N2hUQUFBQkhHbERRMUJwWTJNQUFDaVJZMkJnTW5CMGNYSmwKRW1CZ3lNMHJLUXB5ZDFLSWlJeFNZRC9Qd01iQXpBQUdpY25GQlk0QkFUNGdkbDUrWGlvREJ2aDJqWUVSUkYvV0JabUZLWThYY0NVWApGSlVBNlQ5QWJKU1NXcHpNd01Cb0FHUm5sNWNVQU1VWjV3RFpJa25aWVBZR0VMc29KTWdaeUQ0Q1pQT2xROWhYUU93a0NQc0ppRjBFCjlBU1EvUVdrUGgzTVp1SUFtd05oeTREWUpha1ZJSHNablBNTEtvc3kwek5LRkF3dExTMFZIRlB5azFJVmdpdUxTMUp6aXhVODg1THoKaXdyeWl4SkxVbE9BYWlIdUF3TkJpRUpRaUdrQU5WcG9rdWh2Z2dBVUR4RFc1MEJ3K0RLS25VR0lJVUJ5YVZFWmxNbklaRXlZanpCagpqZ1FEZy85U0JnYVdQd2d4azE0R2hnVTZEQXo4VXhGaWFvWU1EQUw2REF6NzVnQUF3TVpQL2FDSkVFVUFBQUFnWTBoU1RRQUFlaVVBCkFJQ0RBQUQ1L3dBQWdPZ0FBRklJQUFFVldBQUFPcGNBQUJkdjExb2ZrQUFBQUp4UVRGUkZBQUFBLy8vL1pNMy9aTTMvWk0zL1pNMy8KWk0zL1pNMy9aTTMvWk0zL1pNMy9aTTMvWk0zL1pNMy9aTTMvWk0zL1pNMy9LRld2S0ZXdktGV3ZLRld2S0ZXdktGV3ZLRld2S0ZXdgpLRld2S0ZXdktGV3ZLRld2S0ZXdktGV3ZLRld2UnBIWFhiNzFaTTMvS0ZXdkxGMjBScEhYWU1iNk1HUzVUcURoVmEvck4zUERYYjcxClFvclNTcG5jTTJ5K1diZndQNExOTzN2SVVham0vLy8vM3hubHhRQUFBQ0owVWs1VEFBQlFyKy9QanlCQTM3K2ZFREJnZ0hBd2o4L3YKcjJCQXY5OFFVSjl3Z0NDQWdKakg3d2tBQUFBQllrdEhSQUgvQWkzZUFBQUFDWEJJV1hNQUFDNGpBQUF1SXdGNHBUOTJBQUFBQjNSSgpUVVVINWd3SUZRNEVqNDRmM1FBQUE5OUpSRUZVYU43dG05bDYyakFRaFZOTUFnUVNZdGF3SkdsSGFXblRsQzd2LzNBMXhzaXJwSm1SCkJueVJjeS80djZQWkpOdFhWOExxUkYxSWRIM1RrLzRuaFBvRDBMcnRYNXJtQmtvYWppNEpNN3FHaWdaM2JhSkpkTjhxR29DYkMrRkUKMEt6aFJXakdZTklsL09tRFdlZVBuOUhBZ2dPZGMrTkVOaHJvbmpuZisyRFg0S3oxMEw1VkIwWG54Qm03YUFBZVdEOGNUNlpLcWRsOApRVm5VNjdweGdCRSt5MGQxMG1xSlh6WkUwTUExTlh6V2MxWFVCTHZ1RGtORHJvYWJtU3ByaTF4NGk4TUIwdmp6cEdyQzhiaVNQTjh1CkFzMVdOZWc1cERrQVl6OGFwUkR4akRZbktjN1k4ZGxBbzJicmdPWWswek9PNWttWk5BOW9EamFhRjhxc1RVQnprdDZGb0lsbkZweVYKZmJ0bzVtQjZ4WHFsYkxKblYwVEU2VHByODl4S28yWXZsclU5SW8wNzJSZktJVnN4UkhVcmlqMHZNeGVPTXR0RE44ZGx6Nk9UeG1JUAozUnlIUFU5dUdxVml3K0lSWnM2aDJMTjJiMVdpUjhOcXhCQklzMmVMb1RIWnd6UEhZczhTUjJPSW5uc2VqZGtlUkJ4YmtzdDVmRERKCmNDcDFsaHlyUFZ4empKMXJoY1pwc29mV1BFdHFQQ1RqeldteWg5bzhpMnFjZXdqbUtGVnI3TlRtV1ZMUHo1eDZZK2YwaDF4RFQzTnEKWXlxblArU3E1enJObktvOUl5K2FobHlmRW5GV3BkVzgvcENyR3N6WWdweXJkSXZBN0ErNUtzR003RllHZS9nbDhLVHlpZjJGVEZPeQpoOTBmdE1xVkdUWG5WRFRWcXp2ZU5KWDdIbHFXWjlKSFpJLytvRlVzUFRHSFJvOWhQdjFCcTF2QW1iQndUdmI0bGNDVENuMFVOWlBXCk5RL1FIN1R5M05yd2FMSTVJNHc1aGR4aTd0Vnh6dkR0RDFvNnQxaDVkYkxIdHo5b2piM3lLck9IZTM2bzYzUlh5S21CMnA1ZzVnQmsKVXdiNkFORmtqMzkvME1wUzNZTkd2WWFqeVFvemZiWW82R3RBbkdPcVAvdVk4eTBnempGNGZFSW5wRGxaOExUR25MUlBlRlNkd09hawpFelAxQ0NGbkRvQlB3d3B1VHRxMitKRWMzSnhETERObkhRbHpEbDIwUmVaQTVGR1R3NXVUcEJaN0VoUXdKMGt0ZG9zUU1NY0RaeWRCCkErdzgvOTRxSEJsemdIeXZrK21IRUE2UDVrMkdob3Z6czFVNDcwSTBQQnlSQ3NqSCtTVkZ3OExaaTlHd2NINjNDa2NzamxrNE83RTQKWnVFSTFlTlVBektPNUZZbDR4Znhxa2wwcTVKWm1kalJKYmZxOEhvUjdaZ2xNZ0pxZFltSFVNRUNlRkJFTzZLL1NqWHlUQjNhRmJkcwo0QnlmQStDZlpNbDF6cVBHcEN2M1A4STAyZk5aWk9XUm1rZkw1bUR2QnZlaTlTODNCL2NPa1RoTi9nQUpjZXN1VDFONDJqZTlQRTN4ClhmUFlzVjEveFduS0gwN1lrMTIyVWFYZVZMNExzSFN1MTMveU5QVTNRa3o3SmI5UmpkOEV4STN4dkJOdVUyRCtJSEpSSzg5dk1wYzQKR0pnMG9yZUZMZHUvQzU2bk1wWUgxOHYzOGVZNTFlY3ZZMkYxR24zNTFDcDk0SHpnY1BVZm0wUHRsYWp3WU9vQUFBQWxkRVZZZEdSaApkR1U2WTNKbFlYUmxBREl3TWpJdE1USXRNRGhVTWpFNk1UUTZNRFFyTURBNk1EQkEza3NMQUFBQUpYUkZXSFJrWVhSbE9tMXZaR2xtCmVRQXlNREl5TFRFeUxUQTRWREl4T2pFME9qQTBLekF3T2pBd01ZUHp0d0FBQURkMFJWaDBhV05qT21OdmNIbHlhV2RvZEFCRGIzQjUKY21sbmFIUWdNVGs1T1NCQlpHOWlaU0JUZVhOMFpXMXpJRWx1WTI5eWNHOXlZWFJsWkRGcy8yMEFBQUFnZEVWWWRHbGpZenBrWlhOagpjbWx3ZEdsdmJnQkJaRzlpWlNCU1IwSWdLREU1T1RncHNMcnE5Z0FBQUFCSlJVNUVya0pnZ2c9PSIgLz4KPC9zdmc+Cg==';

		add_menu_page
		(
			__('Мой Склад', 'wsklad'),
			__('Мой Склад', 'wsklad'),
			'manage_options',
			'wsklad',
			[$this, 'route'],
			$icon_data_uri,
			30
		);

		add_submenu_page
		(
			'wsklad',
			__('Инструменты', 'wsklad'),
			__('Инструменты', 'wsklad'),
			'manage_options',
			'wsklad_tools',
			[$this, 'route']
		);

		add_submenu_page
		(
			'wsklad',
			__('Настройки', 'wsklad'),
			__('Настройки', 'wsklad'),
			'manage_options',
			'wsklad_settings',
			[Settings::instance(), 'route']
		);

		add_submenu_page
		(
			'wsklad',
			__('Расширения', 'wsklad'),
			__('Расширения', 'wsklad'),
			'manage_options',
			'wsklad_extensions',
			[$this, 'route']
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
			'title' => __('Учетные записи', 'wsklad'),
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
		wp_enqueue_style('wsklad_admin_main', wsklad()->environment()->get('plugin_directory_url') . 'assets/css/main.css');
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
			add_action( 'wsklad_admin_show', [$this, 'wrapHeader'], 3);
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
		wsklad()->views()->getView('header.php');
	}

	/**
	 * Sections
	 */
	public function wrapSections()
	{
		wsklad()->views()->getView('sections.php');
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
		return array_merge(['site' => '<a href="' . admin_url('admin.php?page=wsklad') . '">' . __('Settings', 'wsklad') . '</a>'], $links);
	}
}