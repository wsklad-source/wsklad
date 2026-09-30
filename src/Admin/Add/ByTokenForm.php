<?php namespace Wsklad\Admin\Add;

defined('ABSPATH') || exit;

use Exception;

/**
 * Class ByTokenForm
 *
 * @package Wsklad\Admin\Add
 */
class ByTokenForm extends Form
{
	/**
	 * CreateForm constructor.
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->setId('add-by-token');

		add_filter('wsklad_' . $this->getId() . '_form_load_fields', [$this, 'init_fields_main'], 10);

		$this->init();
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
		$fields['title_token'] =
		[
			'title' => __('Connect by Token', 'wsklad'),
			'type' => 'title',
			'description' => __('Connect using a token you issue in Moy Sklad.', 'wsklad'),
		];

		$fields['token'] =
		[
			'title' => __('Token', 'wsklad'),
			'type' => 'password',
			/**
			 * ⚠ The field is `password`, not `text`, for the same reason the edit form uses
			 * it: a token is a secret, and a plain input puts it on screen and in the DOM
			 * where anyone sharing the screen or opening view-source can read it.
			 *
			 * ⚠ The description is built with `<br />`, not with `<p>`. FormAbstract::
			 * getDescriptionHtml() wraps whatever is here in `<p class="description">`, and
			 * a `<p>` inside a `<p>` is invalid HTML — the browser closes the outer one and
			 * the rest of the form loses its layout.
			 */
			'description' => sprintf
			(
				'%s<br /><b>%s</b> %s<br />%s <a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
				__('Issue a token in your Moy Sklad account, paste it here, then click Connect.', 'wsklad'),
				__('Warning:', 'wsklad'),
				__('issuing a new token in Moy Sklad immediately invalidates the previous one. Replacing the token here stops every account still using the old one — including your other plugins and your other sites.', 'wsklad'),
				__('Issue or revoke tokens here at any time:', 'wsklad'),
				esc_url('https://online.moysklad.ru/app/settings/integrations/tokens'),
				__('Open the Moy Sklad token settings', 'wsklad')
			),
			'default' => '',
			'css' => 'width: 100%;',
		];

		return $fields;
	}
}