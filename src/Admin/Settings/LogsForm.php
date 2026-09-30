<?php namespace Wsklad\Admin\Settings;

defined('ABSPATH') || exit;

use Wsklad\Exceptions\Exception;
use Wsklad\Settings\LogsSettings;

/**
 * LogsForm
 *
 * @package Wsklad\Admin
 */
class LogsForm extends Form
{
	/**
	 * LogsForm constructor.
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->setId('settings-logs');
		$this->setSettings(new LogsSettings());

		add_filter('wsklad_' . $this->getId() . '_form_load_fields', [$this, 'init_fields_logger'], 10);

		$this->init();
	}

	/**
	 * Add settings for logger
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_logger($fields): array
	{
		$fields['logger_level'] =
		[
			'title' => __('Main event level', 'wsklad'),
			'type' => 'select',
			'description' => __('Events at the selected level and above are written to the log file. A higher level records less.', 'wsklad'),
			'default' => '300',
			'options' =>
			[
				'100' => __('DEBUG (100)', 'wsklad'),
				'200' => __('INFO (200)', 'wsklad'),
				'250' => __('NOTICE (250)', 'wsklad'),
				'300' => __('WARNING (300)', 'wsklad'),
				'400' => __('ERROR (400)', 'wsklad'),
			],
		];

		$fields['logger_files_max'] =
		[
			'title' => __('Number of files', 'wsklad'),
			'type' => 'text',
			'description' => __('A new log file is created each day. Older files are deleted once this number is reached; by default that is 30 days of history.', 'wsklad'),
			'default' => 30,
			'css' => 'min-width: 20px;',
		];

		$fields['logger_title_level'] =
		[
			'title' => __('Level per context', 'wsklad'),
			'type' => 'title',
			'description' => __('Set the event level separately for each context.', 'wsklad'),
		];

		$fields['logger_accounts_level'] =
		[
			'title' => __('Accounts', 'wsklad'),
			'type' => 'select',
			'description' => __('Events at the selected level and above are written to the account log. A higher level records less.', 'wsklad'),
			'default' => 'logger_level',
			'options' =>
				[
					'logger_level' => __('Use this level for main events', 'wsklad'),
					'100' => __('DEBUG (100)', 'wsklad'),
					'200' => __('INFO (200)', 'wsklad'),
					'250' => __('NOTICE (250)', 'wsklad'),
					'300' => __('WARNING (300)', 'wsklad'),
					'400' => __('ERROR (400)', 'wsklad'),
				],
		];

		$fields['logger_tools_level'] =
		[
			'title' => __('Tools', 'wsklad'),
			'type' => 'select',
			'description' => __('Events at the selected level and above are written to the tools log. A higher level records less.', 'wsklad'),
			'default' => 'logger_level',
			'options' =>
			[
				'logger_level' => __('Use this level for main events', 'wsklad'),
				'100' => __('DEBUG (100)', 'wsklad'),
				'200' => __('INFO (200)', 'wsklad'),
				'250' => __('NOTICE (250)', 'wsklad'),
				'300' => __('WARNING (300)', 'wsklad'),
				'400' => __('ERROR (400)', 'wsklad'),
			],
		];

		return $fields;
	}
}