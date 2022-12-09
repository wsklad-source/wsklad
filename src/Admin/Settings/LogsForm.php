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
		$this->set_id('settings-logs');
		$this->setSettings(new LogsSettings());

		add_filter('wsklad_' . $this->get_id() . '_form_load_fields', [$this, 'init_fields_logger'], 10);

		$this->init();
	}

	/**
	 * Add settings for logger
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_logger($fields)
	{
		$fields['logger_level'] =
		[
			'title' => __('Level for main events', 'wsklad'),
			'type' => 'select',
			'description' => __('All events of the selected level will be recorded in the log file. The higher the level, the less data is recorded.', 'wsklad'),
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
			'title' => __('Maximum files', 'wsklad'),
			'type' => 'text',
			'description' => __('Log files created daily. This option on the maximum number of stored files. By default saved of the logs are for the last 30 days.', 'wsklad'),
			'default' => 30,
			'css' => 'min-width: 20px;',
		];

		$fields['logger_title_level'] =
		[
			'title' => __('Levels by context', 'wsklad'),
			'type' => 'title',
			'description' => __('Event log settings based on context.', 'wsklad'),
		];

		$fields['logger_receiver_level'] =
		[
			'title' => __('Receiver', 'wsklad'),
			'type' => 'select',
			'description' => __('All events of the selected level will be recorded the Receiver events in the log file. The higher the level, the less data is recorded.', 'wsklad'),
			'default' => 'logger_level',
			'options' =>
			[
				'logger_level' => __('Use level for main events', 'wsklad'),
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
			'description' => __('All events of the selected level will be recorded the tools events in the log file. The higher the level, the less data is recorded.', 'wsklad'),
			'default' => 'logger_level',
			'options' =>
			[
				'logger_level' => __('Use level for main events', 'wsklad'),
				'100' => __('DEBUG (100)', 'wsklad'),
				'200' => __('INFO (200)', 'wsklad'),
				'250' => __('NOTICE (250)', 'wsklad'),
				'300' => __('WARNING (300)', 'wsklad'),
				'400' => __('ERROR (400)', 'wsklad'),
			],
		];

		$fields['logger_schemas_level'] =
		[
			'title' => __('Schemas', 'wsklad'),
			'type' => 'select',
			'description' => __('All events of the selected level will be recorded the schemas events in the log file. The higher the level, the less data is recorded.', 'wsklad'),
			'default' => 'logger_level',
			'options' =>
			[
				'logger_level' => __('Use level for main events', 'wsklad'),
				'100' => __('DEBUG (100)', 'wsklad'),
				'200' => __('INFO (200)', 'wsklad'),
				'250' => __('NOTICE (250)', 'wsklad'),
				'300' => __('WARNING (300)', 'wsklad'),
				'400' => __('ERROR (400)', 'wsklad'),
			],
		];

		$fields['logger_configurations_level'] =
		[
			'title' => __('Configurations', 'wsklad'),
			'type' => 'select',
			'description' => __('All events of the selected level will be recorded the configurations events in the log file. The higher the level, the less data is recorded.', 'wsklad'),
			'default' => 'logger_level',
			'options' =>
			[
				'logger_level' => __('Use level for main events', 'wsklad'),
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