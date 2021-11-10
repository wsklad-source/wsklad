<?php
/**
 * Namespace
 */
namespace Wsklad\Admin\Accounts;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Wsklad\Traits\Sections;
use Wsklad\Traits\Singleton;

/**
 * Class Create
 *
 * @package Wsklad\Admin\Accounts
 */
class Create
{
	use Singleton;
	use Sections;

	/**
	 * Create constructor
	 */
	public function __construct()
	{
		// hook
		do_action(WSKLAD_ADMIN_PREFIX . 'accounts_create_before_loading');

		$this->init();
		$this->route();

		// hook
		do_action(WSKLAD_ADMIN_PREFIX . 'accounts_create_after_loading');
	}

	/**
	 * Initialization
	 */
	public function init()
	{
		// hook
		do_action(WSKLAD_ADMIN_PREFIX . 'accounts_create_before_init');

		$default_sections['login'] =
		[
			'title' => __('Connect by Login & Password', 'wsklad'),
			'visible' => true,
			'callback' => [CreateForm::class, 'instance']
		];

		$default_sections['token'] =
		[
			'title' => __('Connect by Token', 'wsklad'),
			'visible' => true,
			'callback' => [CreateFormByToken::class, 'instance']
		];

		$this->initSections($default_sections);

		// hook
		do_action(WSKLAD_ADMIN_PREFIX . 'accounts_create_after_init');
	}

	/**
	 * Initializing current section
	 *
	 * @return string
	 */
	public function initCurrentSection()
	{
		$current_section = !empty($_GET['do_create']) ? sanitize_title($_GET['do_create']) : 'login';

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
			add_action(WSKLAD_ADMIN_PREFIX . 'show', [$this, 'wrapError']);
		}
		else
		{
			add_action(WSKLAD_ADMIN_PREFIX . 'show', [$this, 'wrapSections'], 7);

			$callback = $sections[$current_section]['callback'];

			if(is_callable($callback, false, $callback_name))
			{
				$callback_name();
			}

			add_action(WSKLAD_ADMIN_PREFIX . 'show', [$this, 'output'], 10);
		}
	}

	/**
	 * Sections
	 */
	public function wrapSections()
	{
		wsklad_get_template('accounts/create_sections.php');
	}

	/**
	 * Error
	 */
	public function wrapError()
	{
		wsklad_get_template('accounts/error.php');
	}

	/**
	 * Show page
	 *
	 * @return void
	 */
	public function output()
	{
		wsklad_get_template('accounts/create.php');
	}
}