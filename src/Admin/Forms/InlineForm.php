<?php namespace Wsklad\Admin\Forms;

defined('ABSPATH') || exit;

use Wsklad\Abstracts\FormAbstract;

/**
 * InlineForm
 *
 * @package Wsklad\Admin
 */
class InlineForm extends FormAbstract
{
	/**
	 * UpdateForm constructor.
	 */
	public function __construct($args = [])
	{
		$this->setId($args['id']);
		$this->loadFields($args['fields']);
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

		wsklad()->views()->getView('inline_form.php', $args);
	}

	/**
	 * Save
	 *
	 * @return array|boolean
	 */
	public function save()
	{
		$post_data = $this->getPostedData();

        $data_key = '_wsklad-admin-nonce-' . $this->getId();

		if(!isset($post_data[$data_key]))
		{
			return false;
		}

		if(empty($post_data) || !wp_verify_nonce($_POST[$data_key], 'wsklad-admin-' . $this->getId() . '-save'))
		{
			wsklad()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => __('Update error. Please retry.', 'wsklad')
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
			catch(\Throwable $e)
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

		return $this->getSavedData();
	}

	/**
	 * Generate Text Input HTML
	 *
	 * @param string $key - field key
	 * @param array $data - field data
	 *
	 * @return string
	 */
	public function generateTextHtml(string $key, array $data): string
	{
		$field_key = $this->getPrefixFieldKey($key);

		$defaults =
		[
			'title' => '',
			'disabled' => false,
			'class' => '',
			'css' => '',
			'placeholder' => '',
			'type' => 'text',
			'desc_tip' => false,
			'description' => '',
			'custom_attributes' => [],
		];

		$data = wp_parse_args($data, $defaults);

		ob_start();
		?>

		<div class="input-group">
			<input placeholder="<?php echo wp_kses_post( $data['title'] ); ?>" aria-label="<?php echo wp_kses_post( $data['title'] ); ?>" class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( $this->getFieldData( $key ) ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->getCustomAttributeHtml( $data ); ?>>
			<button type="submit" class="btn btn-outline-secondary"><?php echo wp_kses_post( $data['button'] ); ?></button>
		</div>
		<?php

		return ob_get_clean();
	}
}