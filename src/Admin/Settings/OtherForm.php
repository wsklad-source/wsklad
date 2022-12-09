<?php
/**
 * Namespace
 */
namespace Wsklad\Admin\Settings;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */

use Exception;
use Wsklad\Settings\OtherSettings;

/**
 * Class OtherForm
 *
 * @package Wsklad\Admin\Settings
 */
class OtherForm extends Form
{
	/**
	 * OtherForm constructor.
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->set_id('settings-other');
		$this->setSettings(new OtherSettings());

		add_filter('wsklad_' . $this->get_id() . '_form_load_fields', [$this, 'init_fields_main'], 10);

		$this->init();
	}

	/**
	 * Main fields
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_main($fields)
	{
		$fields['tracking'] =
		[
			'title' => __('Usage data', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Allow sending usage data to WSklad servers?', 'wsklad'),
			'description' => __('If enabled, the site will send anonymous data about plugin usage to WSklad servers once a week in the background.', 'wsklad'),
			'default' => 'no'
		];

		return $fields;
	}
}