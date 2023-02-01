<?php namespace Wsklad\Admin\Accounts;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Wsklad\Admin\Forms\InlineForm;
use Wsklad\Admin\Traits\ProcessAccountTrait;
use Wsklad\Data\Storage;
use Wsklad\Traits\DatetimeUtilityTrait;
use Wsklad\Traits\SectionsTrait;
use Wsklad\Traits\UtilityTrait;

/**
 * Update
 *
 * @package Wsklad\Admin
 */
class Update
{
	use SingletonTrait;
	use ProcessAccountTrait;
	use DatetimeUtilityTrait;
	use UtilityTrait;
	use SectionsTrait;

	/**
	 * Update constructor.
	 */
	public function __construct()
	{
		$this->setSectionKey('update_section');

		$default_sections['main'] =
		[
			'title' => __('Main', 'wsklad'),
			'visible' => true,
			'callback' => [MainUpdate::class, 'instance']
		];

		if(has_action('wsklad_admin_accounts_update_sections'))
		{
			$default_sections = apply_filters('wsklad_admin_accounts_update_sections', $default_sections);
		}

		$this->initSections($default_sections);
		$this->setCurrentSection('main');

		$account_id = wsklad()->getVar($_GET['account_id'], 0);

		if(false === $this->setAccount($account_id))
		{
			$this->process();
		}
		else
		{
			add_action('wsklad_admin_show', [$this, 'outputError'], 10);
			wsklad()->log()->notice('Account update is not available.', ['configuration_id' => $account_id]);
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

		if(!array_key_exists($current_section, $sections) || !isset($sections[$current_section]['callback']))
		{
			add_action('wsklad_admin_accounts_update_show', [$this, 'wrapError']);
		}
		else
		{
			add_action('wsklad_admin_before_accounts_update_show', [$this, 'wrapSections'], 5);

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
		$configuration = $this->getAccount();

		$fields['name'] =
		[
			'title' => __('Account name', 'wsklad'),
			'type' => 'text',
			'description' => __('Used for convenient distribution of multiple accounts.', 'wsklad'),
			'default' => '',
			'class' => 'form-control form-control-sm',
			'button' => __('Rename', 'wsklad'),
		];

		$inline_args =
		[
			'id' => 'accounts-name',
			'fields' => $fields
		];

		$inline_form = new InlineForm($inline_args);
		$inline_form->loadSavedData(['name' => $configuration->get_name()]);

		if(isset($_GET['form']) && $_GET['form'] === $inline_form->getId())
		{
			$configuration_name = $inline_form->save();

			if(isset($configuration_name['name']))
			{
				$configuration->set_date_modify(time());
				$configuration->set_name($configuration_name['name']);

				$saved = $configuration->save();

				if($saved)
				{
					wsklad()->admin()->notices()->create
					(
						[
							'type' => 'update',
							'data' => __('Account name update success.', 'wsklad')
						]
					);
				}
				else
				{
					wsklad()->admin()->notices()->create
					(
						[
							'type' => 'error',
							'data' => __('Account name update error. Please retry saving or change fields.', 'wsklad')
						]
					);
				}
			}
		}

		add_action('wsklad_admin_accounts_update_header_show', [$inline_form, 'outputForm'], 10);
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

		wsklad()->views()->getView('accounts/update_error.php', $args);
	}

	/**
	 * Sections
	 *
	 * @return void
	 */
	public function wrapSections()
	{
		$args['object'] = $this;

		wsklad()->views()->getView('accounts/update_sections.php', $args);
	}

	/**
	 * Output
	 *
	 * @return void
	 */
	public function output()
	{
		$accounts = new Storage('account');
		$total_items = $accounts->count();

		$args = [];

		if($total_items > 1)
		{
			$args['back_url'] = $this->utilityAdminAccountsGetUrl('all');
		}

		wsklad()->views()->getView('accounts/update.php', $args);
	}
}