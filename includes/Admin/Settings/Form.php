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
use Wsklad\Abstracts\FormAbstract;
use Wsklad\Interfaces\SettingsInterface;
use Wsklad\Traits\Singleton;

/**
 * Class Form
 *
 * @package Wsklad\Admin\Settings
 */
abstract class Form extends FormAbstract
{
	/**
	 * Traits
	 */
	use Singleton;

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
		add_action(WSKLAD_ADMIN_PREFIX . 'show', [$this, 'output_form']);
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
			wsklad_admin()->messages()->addMessage('error', __('Save error. Please retry.', 'wsklad'));
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
				wsklad_admin()->messages()->addMessage('error', $e->getMessage());
			}
		}

		try
		{
			$this->getSettings()->set($this->get_saved_data());
			$this->getSettings()->save();
		}
		catch(Exception $e)
		{
			wsklad_admin()->messages()->addMessage('error', $e->getMessage());
			return false;
		}

		wsklad_admin()->messages()->addMessage('update', __('Save success.', 'wsklad'));
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

		wsklad_get_template('settings/form.php', $args);
	}
}