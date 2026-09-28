<?php namespace Wsklad\Admin\Settings;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Interfaces\SettingsInterface;
use Digiom\Woplucore\Traits\SingletonTrait;
use Exception;
use Wsklad\Abstracts\FormAbstract;

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
		$this->loadFields();
		$this->getSettings()->init();
		$this->loadSavedData($this->getSettings()->get());
		$this->preserveRemovedFields();
		$this->save();

		add_action('wsklad_admin_show', [$this, 'outputForm']);
	}

	/**
	 * Settings keys this form no longer renders, mapped to the value to keep for each.
	 *
	 * A key is only removed from the UI after nothing in the plugin reads it any
	 * more. It is still listed here, and still written back to the stored option,
	 * so that a site upgrading from an older release keeps the value the user
	 * chose and an extension reading the key gets that value rather than `null`.
	 *
	 * The map lives on the settings class as `LEGACY_DEFAULTS`, because the option
	 * it describes belongs to the settings class, not to the form that renders it.
	 *
	 * @return array
	 */
	public function getPreservedSettings(): array
	{
		$settings = $this->getSettings();

		if(is_null($settings))
		{
			return [];
		}

		$constant = get_class($settings) . '::LEGACY_DEFAULTS';

		if(!defined($constant))
		{
			return [];
		}

		$defaults = constant($constant);

		return is_array($defaults) ? $defaults : [];
	}

	/**
	 * Keep the removed keys in the saved data, so that saving does not drop them.
	 *
	 * `save()` rebuilds `saved_data` from the fields this form renders. Without
	 * this, the first save after an upgrade would write the option back without
	 * the removed keys and the stored value would be lost for good. A value that
	 * is already stored always wins; this only fills in what is missing.
	 */
	protected function preserveRemovedFields()
	{
		foreach($this->getPreservedSettings() as $key => $default)
		{
			if(!array_key_exists($key, $this->saved_data))
			{
				$this->saved_data[$key] = $default;
			}
		}
	}

	/**
	 * Save
	 *
	 * @return bool
	 */
	public function save()
	{
		$post_data = $this->getPostedData();

		if(!isset($post_data['_wsklad-admin-nonce']))
		{
			return false;
		}

		if(empty($post_data) || !wp_verify_nonce(sanitize_text_field(wp_unslash($post_data['_wsklad-admin-nonce'])), 'wsklad-admin-settings-save'))
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
		foreach($this->getFields() as $key => $field)
		{
			if('title' === $this->getFieldType($field))
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
			}
		}

		try
		{
			$this->getSettings()->set($this->getSavedData());
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
	public function outputForm()
	{
		$args =
		[
			'object' => $this
		];

		wsklad()->views()->getView('settings/form.php', $args);
	}
}