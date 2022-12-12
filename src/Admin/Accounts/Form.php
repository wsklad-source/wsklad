<?php namespace Wsklad\Admin\Accounts;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Exception;
use Wsklad\Abstracts\FormAbstract;
use Wsklad\Account;
use Wsklad\Traits\AccountsUtilityTrait;
use Wsklad\Traits\UtilityTrait;

/**
 * Class Form
 *
 * @package Wsklad\Admin\Accounts
 */
abstract class Form extends FormAbstract
{
	use SingletonTrait;
	use AccountsUtilityTrait;
	use UtilityTrait;

	/**
	 * Lazy load
	 *
	 * @throws Exception
	 */
	protected function init()
	{
		add_filter('wsklad_' . $this->get_id() . '_form_load_fields', [$this, 'init_fields_name'], 5);
		add_filter('wsklad_' . $this->get_id() . '_form_load_fields', [$this, 'init_fields_test'], 15);

		$this->load_fields();
		$this->save();

		add_action('wsklad_admin_accounts_form_create_show', [$this, 'output_form']);
	}

	/**
	 * Add for name
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_name($fields)
	{
		$fields['name'] =
		[
			'title' => __('Name', 'wsklad'),
			'type' => 'text',
			'description' => __('An arbitrary name for the connection. Used for reference purposes.', 'wsklad'),
			'default' => '',
			'css' => 'width: 100%;',
		];

		return $fields;
	}

	/**
	 * Add for test
	 *
	 * @param $fields
	 *
	 * @return array
	 * @throws Exception
	 */
	public function init_fields_test($fields)
	{
		$fields['test'] =
		[
			'title' => __('Test', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Test connection before adding?', 'wsklad'),
			'default' => wsklad()->settings()->get('accounts_test_before_add', 'yes'),
		];

		return $fields;
	}

	/**
	 * Save
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function save()
	{
		$post_data = $this->get_posted_data();

		if(!isset($post_data['_wsklad-admin-nonce-accounts-create']))
		{
			return false;
		}

		if(empty($post_data) || !wp_verify_nonce($post_data['_wsklad-admin-nonce-accounts-create'], 'wsklad-admin-accounts-create-save'))
		{
			wsklad()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => __('Connection error. Please retry.', 'wsklad')
				]
			);

			return false;
		}

		foreach($this->get_fields() as $key => $field)
		{
			$field_type = $this->get_field_type($field);

			if('title' === $field_type || 'raw' === $field_type)
			{
				continue;
			}

			try
			{
				$this->saved_data[$key] = $this->get_field_value($key, $field, $post_data);
			}
			catch(Exception $e)
			{
				wsklad()->admin()->notices()->create
				(
					[
						'type' => 'error',
						'data' => $e->getMessage()
					]
				);
			}
		}

		$data = $this->get_saved_data();

		if(empty($data['name']))
		{
			wsklad()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => __('Account connection error. Name is required.', 'wsklad')
				]
			);

			return false;
		}

		$account_type = 'login';
		if(!empty($data['token']))
		{
			$account_type = 'token';
		}

		if('login' === $account_type)
		{
			if(empty($data['login']))
			{
				wsklad()->admin()->notices()->create
				(
					[
						'type' => 'error',
						'data' => __('Account connection error. Login is required.', 'wsklad')
					]
				);

				return false;
			}

			if(empty($data['password']))
			{
				wsklad()->admin()->notices()->create
				(
					[
						'type' => 'error',
						'data' => __('Account connection error. Password is required.', 'wsklad')
					]
				);

				return false;
			}
		}

		if('token' === $account_type && empty($data['token']))
		{
			wsklad()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => __('Account connection error. Token is required.', 'wsklad')
				]
			);

			return false;
		}

		$account = new Account();
		$data_storage = $account->get_storage();
		$account->set_connection_type($account_type);
		$account->set_status('draft');

		if('yes' === wsklad()->settings()->get('accounts_unique_name', 'yes') && $data_storage->is_existing_by_name($data['name']))
		{
			wsklad()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => __('Account connection error. Name is exists.', 'wsklad')
				]
			);

			return false;
		}

		$account->set_name($data['name']);

		if('login' === $account_type)
		{
			if($data_storage->is_existing_by_login($data['login']))
			{
				wsklad()->admin()->notices()->create
				(
					[
						'type' => 'error',
						'data' => __('Account connection error. Login is exists.', 'wsklad')
					]
				);

				return false;
			}

			$account->set_moysklad_login($data['login']);
			$account->set_moysklad_password($data['password']);
		}

		if('token' === $account_type)
		{
			if($data_storage->is_existing_by_token($data['token']))
			{
				wsklad()->admin()->notices()->create
				(
					[
						'type' => 'error',
						'data' => __('Account connection error. Token is exists.', 'wsklad')
					]
				);

				return false;
			}

			$account->set_moysklad_token($data['token']);
		}

		/**
		 * Test connection
		 */
		if('yes' === $data['test'])
		{
			try
			{
				$response = $account->moysklad()->entity()->employee()->getList();
				$response = json_decode($response, true);

				if(!isset($response['meta']))
				{
					wsklad()->admin()->notices()->create
					(
						[
							'type' => 'error',
							'data' => __('Account connection error. Test connection is not success.', 'wsklad')
						]
					);
					return false;
				}
			}
			catch(Exception $e)
			{
				wsklad()->admin()->notices()->create
				(
					[
						'type' => 'error',
						'data' => $e->getMessage()
					]
				);
				return false;
			}
		}

		if($account->save())
		{
			wsklad()->admin()->notices()->create
			(
				[
					'type' => 'update',
					'data' => __('Account connection success. Account connection id:', 'wsklad') . ' ' . $account->get_id()
					           . ' (<a href="' . $this->utilityAdminAccountsGetUrl('update', $account->get_id()) . '">' . __('edit account', 'wsklad') . '</a>)'
				]
			);

			$this->set_saved_data([]);
			return true;
		}

		wsklad()->admin()->notices()->create
		(
			[
				'type' => 'error',
				'data' => __('Account connection error. Please retry saving or change fields.', 'wsklad')
			]
		);

		return false;
	}

	/**
	 * Form show
	 */
	public function output_form()
	{
		$args =
		[
			'object' => $this,
			'back_url' => $this->utilityAdminAccountsGetUrl()
		];

		wsklad()->views()->getView('accounts/create_form.php', $args);
	}
}