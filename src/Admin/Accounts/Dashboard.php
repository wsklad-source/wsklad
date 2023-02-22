<?php namespace Wsklad\Admin\Accounts;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Wsklad\Admin\Traits\ProcessAccountTrait;
use Wsklad\Traits\AccountsUtilityTrait;
use Wsklad\Traits\DatetimeUtilityTrait;
use Wsklad\Traits\SectionsTrait;
use Wsklad\Traits\UtilityTrait;

/**
 * Dashboard
 *
 * @package Wsklad\Admin\Accounts
 */
class Dashboard
{
	use SingletonTrait;
	use ProcessAccountTrait;
	use DatetimeUtilityTrait;
	use UtilityTrait;
	use SectionsTrait;
    use AccountsUtilityTrait;

	/**
	 * Dashboard constructor.
	 */
	public function __construct()
	{
		$this->setSectionKey('dashboard_section');

		$default_sections['main'] =
		[
			'title' => __('Settings', 'wsklad'),
            'priority' => 5,
			'visible' => true,
			'callback' => [MainUpdate::class, 'instance'],
			'description' => __('Updating the parameters of all basic settings, including data for authorization in Moy Sklad.', 'wsklad'),
		];

		if(has_action('wsklad_admin_accounts_dashboard_sections'))
		{
			$default_sections = apply_filters('wsklad_admin_accounts_dashboard_sections', $default_sections);
		}

		$this->initSections($default_sections);
		$this->setCurrentSection('');

		$account_id = wsklad()->getVar($_GET['account_id'], 0);

		if(false === $this->setAccount($account_id))
		{
			$this->process();
		}
		else
		{
			add_action('wsklad_admin_show', [$this, 'outputError'], 10);
			wsklad()->log()->notice('Account is not available.', ['account_id' => $account_id]);
			return;
		}

		$this->route();

		add_action('wsklad_admin_show', [$this, 'output'], 10);
	}

	/**
	 *  Routing
	 */
	public function route()
	{
		$sections = $this->getSections();
		$current_section = $this->initCurrentSection();

		if(empty($current_section))
		{
			add_action('wsklad_admin_accounts_dashboard_show', [$this, 'wrapSections'], 5);
			add_action('wsklad_admin_accounts_dashboard_sidebar_show', [$this, 'outputSidebar'], 10);

			return;
		}

		if(!array_key_exists($current_section, $sections) || !isset($sections[$current_section]['callback']))
		{
			add_action('wsklad_admin_accounts_dashboard_show', [$this, 'wrapError']);
		}
		else
		{
			$callback = $sections[$current_section]['callback'];

			if(is_callable($callback, false, $callback_name))
			{
				$callback_obj = $callback_name();
				$callback_obj->setAccount($this->getAccount());
				$callback_obj->process();
			}
		}
	}

	/**
	 * Update processing
	 */
	public function process()
	{
		add_action('wsklad_admin_header_items_show', [$this, 'headerItem'], 10);
	}

	public function headerItem()
	{
		$account = $this->getAccount();
		echo wp_kses_post(' > ' . $account->get_name());
	}

	/**
	 * Error
	 */
	public function wrapError()
	{
		wsklad()->views()->getView('error.php');
	}

	/**
	 * Output error
	 */
	public function outputError()
	{
		$args['back_url'] = $this->utilityAdminAccountsGetUrl('all');

		wsklad()->views()->getView('accounts/error.php', $args);
	}

	/**
	 * Sections
	 *
	 * @return void
	 */
	public function wrapSections()
	{
		$args['object'] = $this;

		wsklad()->views()->getView('accounts/sections.php', $args);
	}

	/**
	 * Output
	 *
	 * @return void
	 */
	public function output()
	{
		$args = [];
		$section = $this->initCurrentSection();

		if($section)
		{
			$name = '';
			$sections = $this->getSections();
			if(isset($sections[$section]['title']))
			{
				$name = $sections[$section]['title'];
			}

			wsklad()->views()->getView('accounts/sections_single.php', ['object' => $this, 'name' => $name]);

			return;
		}

		wsklad()->views()->getView('accounts/dashboard.php', $args);
	}

	/**
	 * Sidebar show
	 */
	public function outputSidebar()
	{
		$account = $this->getAccount();

		$args =
		[
			'header' => '<h3 class="p-0 m-0">' . __('About account', 'wsklad') . '</h3>',
			'object' => $this
		];

		$body = '<ul class="list-group m-0 list-group-flush">';

        $body .= '<li class="list-group-item p-2 m-0">';
        $body .= __('Status', 'wsklad') . ': <b>' . $this->utilityAccountsGetStatusesLabel($account->get_status()) . '</b>';
        $body .= '</li>';

        $body .= '<li class="list-group-item p-2 m-0">';
        $body .= __('Date active:', 'wsklad') . '<div class="p-1 mt-1 bg-light">' . $this->utilityPrettyDate($account->get_date_activity());

        if($account->get_date_activity())
        {
            $body .= sprintf(_x(' (%s ago).', '%s = human-readable time difference', 'wsklad'), human_time_diff($account->get_date_activity()->getOffsetTimestamp(), current_time('timestamp')));
        }
        $body .= '</div></li>';

		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('ID:', 'wsklad') . ' <b>' . $account->get_id() . '</b>';
		$body .= '</li>';

		$body .= '<li class="list-group-item p-2 m-0">';
		$user_id = $account->get_user_id();
		$user = get_userdata($user_id);
		if($user instanceof \WP_User && $user->exists())
		{
			$body .= __('Owner:', 'wsklad') . ' <b>' . $user->get('nickname') . '</b> (' . $user_id. ')';
		}
		else
		{
			$body .= __('User is not exists.', 'wsklad');
		}
		$body .= '</li>';

		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('Date create:', 'wsklad') . '<div class="p-1 mt-1 bg-light">' . $this->utilityPrettyDate($account->get_date_create());

		if($account->get_date_create())
		{
			$body .= sprintf(_x(' (%s ago).', '%s = human-readable time difference', 'wsklad'), human_time_diff($account->get_date_create()->getOffsetTimestamp(), current_time('timestamp')));
		}

		$body .= '</div></li>';
		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('Date modify:', 'wsklad') . '<div class="p-1 mt-1 bg-light">'. $this->utilityPrettyDate($account->get_date_modify());

		if($account->get_date_modify())
		{
			$body .= sprintf(_x(' (%s ago).', '%s = human-readable time difference', 'wsklad'), human_time_diff($account->get_date_modify()->getOffsetTimestamp(), current_time('timestamp')));
		}

		$body .= '</div></li>';

		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('Directory:', 'wsklad') . '<div class="p-1 mt-1 bg-light">' . wp_normalize_path($account->get_upload_directory()) . '</div>';
		$body .= '</li>';

		$size = 0;
		$files = wsklad()->filesystem()->files($account->get_upload_directory('uploads'));

		foreach($files as $file)
		{
			$size += wsklad()->filesystem()->size($file);
		}

		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('Directory size:', 'wsklad') . ' <b>' . size_format($size) . '</b>';
		$body .= '</li>';

		$size = 0;
		$files = wsklad()->filesystem()->files($account->get_upload_directory('logs'));

		foreach($files as $file)
		{
			$size += wsklad()->filesystem()->size($file);
		}

		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('Logs directory size:', 'wsklad') . ' <b>' . size_format($size) . '</b>';
		$body .= '</li>';

		$body .= '</ul>';

		$args['body'] = $body;

		wsklad()->views()->getView('accounts/sidebar_item.php', $args);
	}
}