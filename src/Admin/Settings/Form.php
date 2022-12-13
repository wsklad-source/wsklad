<?php namespace Wsklad\Admin\Settings;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Exception;
use Wsklad\Abstracts\FormAbstract;
use Wsklad\Interfaces\SettingsInterface;

/**
 * Class Form
 *
 * @package Wsklad\Admin\Settings
 */
abstract class Form extends FormAbstract
{
	use SingletonTrait;

	/**
	 * @var SettingsInterface
	 */
	public $settings;

	/**
	 * @return SettingsInterface
	 */
	public function getSettings()
	{
		return $this->settings;
	}

	/**
	 * @param SettingsInterface $settings
	 */
	public function setSettings($settings)
	{
		$this->settings = $settings;
	}

	/**
	 * Lazy load
	 *
	 * @throws Exception
	 */
	protected function init()
	{
		$this->load_fields();
		$this->getSettings()->init();
		$this->load_saved_data($this->getSettings()->get());
		$this->save();

		add_action('wsklad_admin_show', [$this, 'output_form']);
	}

	/**
	 * Save
	 *
	 * @return bool
	 */
	public function save()
	{
		$post_data = $this->get_posted_data();

		if(!isset($post_data['_wsklad-admin-nonce']))
		{
			return false;
		}

		if(empty($post_data) || !wp_verify_nonce($post_data['_wsklad-admin-nonce'], 'wsklad-admin-settings-save'))
		{
			wsklad()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => __('Save error. Please retry.', 'wsklad')
				]
			);

			return false;
		}

		/**
		 * All form fields validate
		 */
		foreach($this->get_fields() as $key => $field)
		{
			if('title' === $this->get_field_type($field))
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

		try
		{
			$this->getSettings()->set($this->get_saved_data());
			$this->getSettings()->save();
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

		wsklad()->admin()->notices()->create
		(
			[
				'type' => 'update',
				'data' => __('Save success.', 'wsklad')
			]
		);

		return true;
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

		wsklad()->views()->getView('settings/form.php', $args);
	}
}