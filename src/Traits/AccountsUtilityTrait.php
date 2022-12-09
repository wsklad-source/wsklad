<?php namespace Wsklad\Traits;

defined('ABSPATH') || exit;

/**
 * AccountsUtilityTrait
 *
 * @package Wsklad\Traits
 */
trait AccountsUtilityTrait
{
	/**
	 * Get all available Accounts statuses
	 *
	 * @return array
	 */
	public function utilityAccountsGetStatuses(): array
	{
		$statuses =
		[
			'draft',
			'inactive',
			'active',
			'processing',
			'error',
			'deleted',
		];

		return apply_filters('wsklad_accounts_get_statuses', $statuses);
	}

	/**
	 * Get normal Accounts status
	 *
	 * @param string $status
	 *
	 * @return string
	 */
	public function utilityAccountsGetStatusesLabel(string $status): string
	{
		$default_label = __('Undefined', 'wsklad');

		$statuses_labels = apply_filters
		(
			'wsklad_accounts_get_statuses_labels',
			[
				'draft' => __('Draft', 'wsklad'),
				'active' => __('Active', 'wsklad'),
				'inactive' => __('Inactive', 'wsklad'),
				'error' => __('Error', 'wsklad'),
				'processing' => __('Processing', 'wsklad'),
				'deleted' => __('Deleted', 'wsklad'),
			]
		);

		if(empty($status) || !array_key_exists($status, $statuses_labels))
		{
			$status_label = $default_label;
		}
		else
		{
			$status_label = $statuses_labels[$status];
		}

		return apply_filters('wsklad_accounts_get_statuses_label_return', $status_label, $status, $statuses_labels);
	}

	/**
	 * Get normal Accounts types
	 *
	 * @param string $status
	 *
	 * @return string
	 */
	public function utilityAccountsGetTypesLabel(string $status): string
	{
		$default_label = __('Undefined', 'wsklad');

		$statuses_labels = apply_filters
		(
			'wsklad_accounts_get_types_labels',
			[
				'token' => __('by Token', 'wsklad'),
				'login' => __('by Login & Password', 'wsklad'),
			]
		);

		if(empty($status) || !array_key_exists($status, $statuses_labels))
		{
			$status_label = $default_label;
		}
		else
		{
			$status_label = $statuses_labels[$status];
		}

		return apply_filters('wsklad_accounts_get_types_label_return', $status_label, $status, $statuses_labels);
	}

	/**
	 * Get folder name for account statuses
	 *
	 * @param string $status
	 *
	 * @return string
	 */
	public function utilityAccountsGetStatusesFolder(string $status): string
	{
		$default_folder = __('Undefined', 'wsklad');

		$statuses_folders = apply_filters
		(
			'wsklad_accounts_get_statuses_folders',
			[
				'draft' => __('Drafts', 'wsklad'),
				'active' => __('Activated', 'wsklad'),
				'inactive' => __('Deactivated', 'wsklad'),
				'error' => __('With errors', 'wsklad'),
				'processing' => __('In processing', 'wsklad'),
				'deleted' => __('Trash', 'wsklad'),
			]
		);

		$status_folder = $default_folder;

		if(!empty($status) || array_key_exists($status, $statuses_folders))
		{
			$status_folder = $statuses_folders[$status];
		}

		return apply_filters('wsklad_accounts_get_statuses_folder_return', $status_folder, $status, $statuses_folders);
	}
}