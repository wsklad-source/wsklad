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
use Wsklad\Abstracts\FormAbstract;
use Wsklad\Account;

/**
 * Class CreateFormByToken
 *
 * @package Wsklad\Admin\Accounts
 */
class CreateFormByToken extends FormAbstract
{
	/**
	 * CreateForm constructor.
	 */
	public function __construct()
	{
		$this->set_id('accounts-create-by-token');
	}

	/**
	 * Output
	 */
	public function output_form()
	{
		$args =
		[
			'object' => $this
		];

		wsklad_get_template('accounts/create_form_by_token.php', $args);
	}

	/**
	 * Save form data in DB
	 *
	 * @return bool
	 */
	public function save()
	{
		$post_data = $this->get_posted_data();

		if(!isset($post_data['_wsklad-admin-nonce-accounts-create-by-token']))
		{
			return false;
		}

		if(empty($post_data) || !wp_verify_nonce($post_data['_wsklad-admin-nonce-accounts-create-by-token'], 'wsklad-admin-accounts-create-by-token-save'))
		{
			wsklad_admin()->messages()->addMessage('error', __('Connection error. Please retry.', 'wsklad'));
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
			catch(\Exception $e)
			{
				wsklad_admin()->messages()->addMessage('error', $e->getMessage());
			}
		}

		$data = $this->get_saved_data();

		if(empty($data['token']))
		{
			wsklad_admin()->messages()->addMessage('error', __('Account connection error. Token is required.', 'wsklad'));
			return false;
		}

		$account = new Account();

		$data_storage = $account->get_storage();

		if($data_storage->is_existing_by_name($data['token']))
		{
			wsklad_admin()->messages()->addMessage('error', __('Account connection error. Token is exists.', 'wsklad'));
			return false;
		}

		/**
		 * Test connection
		 */
		// todo: check connection, get account data


		$account->set_status('draft');
		$account->set_name($data['name']);

		if($account->save())
		{
			wsklad_admin()->messages()->addMessage
			(
				'update',
				__('Account connection success. Account connection id: ' . $account->get_id(), 'wsklad')
				. ' (<a href="' . wsklad_admin_accounts_get_url('update', $account->get_id()) . '">' . __('edit account', 'wsklad') . '</a>)'
			);

			$this->set_saved_data([]);
			return true;
		}

		wsklad_admin()->messages()->addMessage('error', __('Account connection error. Please retry saving or change fields.', 'wsklad'));
		return false;
	}
}