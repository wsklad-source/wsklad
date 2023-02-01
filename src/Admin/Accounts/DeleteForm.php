<?php namespace Wsklad\Admin\Accounts;

defined('ABSPATH') || exit;

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
		$this->setId('accounts-delete');

		add_filter('wsklad_' . $this->getId() . '_form_load_fields', [$this, 'init_fields_main'], 10);

		$this->loadFields();
	}

	/**
	 * Add for Main
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function init_fields_main(array $fields): array
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
	public function outputForm()
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
		$post_data = $this->getPostedData();

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

		foreach($this->getFields() as $key => $field)
		{
			$field_type = $this->getFieldType($field);

			if('title' === $field_type || 'raw' === $field_type)
			{
				continue;
			}

			try
			{
				$this->saved_data[$key] = $this->getFieldValue($key, $field, $post_data);
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

		$data = $this->getSavedData();

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