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

use Exception;
use Wsklad\Abstracts\FormAbstract;

/**
 * Class DeleteForm
 *
 * @package Wsklad\Admin\Accounts
 */
class DeleteForm extends FormAbstract
{
	/**
	 * DeleteForm constructor.
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->set_id('accounts-delete');

		add_filter('wsklad_' . $this->get_id() . '_form_load_fields', [$this, 'init_fields_main'], 10);

		$this->load_fields();
	}

	/**
	 * Add for Main
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_main($fields)
	{
		$fields['accept'] =
		[
			'title' => __('Delete confirmation', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('I confirm that Moy Sklad account will be permanently and irrevocably deleted from WordPress.', 'wsklad'),
			'default' => 'no',
		];

		return $fields;
	}

	/**
	 * Form show
	 */
	public function output_form()
	{
		$args =
		[
			'object' => $this
		];

		wsklad()->views()->getView('accounts/delete_form.php', $args);
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

		if(!isset($post_data['_wsklad-admin-nonce-accounts-delete']))
		{
			return false;
		}

		if(empty($post_data) || !wp_verify_nonce($post_data['_wsklad-admin-nonce-accounts-delete'], 'wsklad-admin-accounts-delete-save'))
		{
			wsklad()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => __('Delete error. Please retry.', 'wsklad')
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

				return false;
			}
		}

		$data = $this->get_saved_data();

		if(!isset($data['accept']) || $data['accept'] !== 'yes')
		{
			wsklad()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => __('Delete error. Confirmation of final deletion is required.', 'wsklad')
				]
			);

			return false;
		}

		return true;
	}
}