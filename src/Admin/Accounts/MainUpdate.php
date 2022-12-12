<?php namespace Wsklad\Admin\Accounts;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Wsklad\Admin\Traits\ProcessAccountTrait;
use Wsklad\Traits\DatetimeUtilityTrait;
use Wsklad\Traits\SectionsTrait;
use Wsklad\Traits\UtilityTrait;

/**
 * MainUpdate
 *
 * @package Wsklad\Admin
 */
class MainUpdate
{
	use SingletonTrait;
	use DatetimeUtilityTrait;
	use UtilityTrait;
	use SectionsTrait;
	use ProcessAccountTrait;

	/**
	 * Update processing
	 */
	public function process()
	{
		$account = $this->getAccount();
		$form = new UpdateForm();

		$form_data = $account->get_options();
		$form_data['status'] = $account->get_status();

		$form->load_saved_data($form_data);

		if(isset($_GET['form']) && $_GET['form'] === $form->get_id())
		{
			$data = $form->save();

			if($data)
			{
				$account->set_status($data['status']);
				unset($data['status']);

				$account->set_date_modify(time());
				$account->set_options($data);

				$saved = $account->save();

				if($saved)
				{
					wsklad()->admin()->notices()->create
					(
						[
							'type' => 'update',
							'data' => __('Account update success.', 'wsklad')
						]
					);
				}
				else
				{
					wsklad()->admin()->notices()->create
					(
						[
							'type' => 'error',
							'data' => __('Account update error. Please retry saving or change fields.', 'wsklad')
						]
					);
				}
			}
		}

		add_action('wsklad_admin_accounts_update_sidebar_show', [$this, 'outputSidebar'], 10);
		add_action('wsklad_admin_accounts_update_show', [$form, 'output_form'], 10);
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
		$body .= __('ID: ', 'wsklad') . $account->get_id();
		$body .= '</li>';

		$body .= '<li class="list-group-item p-2 m-0">';
		$user_id = $account->get_user_id();
		$user = get_userdata($user_id);
		if($user instanceof \WP_User && $user->exists())
		{
			$body .= __('User: ', 'wsklad') . $user->get('nickname') . ' (' . $user_id. ')';
		}
		else
		{
			$body .= __('User is not exists.', 'wsklad');
		}
		$body .= '</li>';

		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('Date create: ', 'wsklad') . $this->utilityPrettyDate($account->get_date_create());
		$body .= '</li>';
		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('Date modify: ', 'wsklad') . $this->utilityPrettyDate($account->get_date_modify());
		$body .= '</li>';
		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('Date active: ', 'wsklad') . $this->utilityPrettyDate($account->get_date_activity());
		$body .= '</li>';

		$body .= '</ul>';

		$args['body'] = $body;
		//$args['css'] = 'margin-top:-35px!important;';

		wsklad()->views()->getView('accounts/update_sidebar_item.php', $args);
	}
}