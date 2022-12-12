<?php namespace Wsklad\Admin\Forms;

defined('ABSPATH') || exit;

use Wsklad\Exceptions\Exception;
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
		$this->set_id($args['id']);
		$this->load_fields($args['fields']);
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

		wsklad()->views()->getView('inline_form.php', $args);
	}

	/**
	 * Save
	 *
	 * @return array|boolean
	 */
	public function save()
	{
		$post_data = $this->get_posted_data();

        $data_key = '_wsklad-admin-nonce-' . $this->get_id();

		if(!isset($post_data[$data_key]))
		{
			return false;
		}

		if(empty($post_data) || !wp_verify_nonce($_POST[$data_key], 'wsklad-admin-' . $this->get_id() . '-save'))
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

		foreach($this->get_fields() as $key => $field)
		{
			$field_type = $this->get_field_type($field);

			if('title' === $field_type || 'raw' === $field_type)
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

				return false;
			}
		}

		return $this->get_saved_data();
	}

	/**
	 * Generate Text Input HTML
	 *
	 * @param string $key - field key
	 * @param array $data - field data
	 *
	 * @return string
	 */
	public function generate_text_html($key, $data)
	{
		$field_key = $this->get_prefix_field_key($key);

		$defaults = array
		(
			'title' => '',
			'disabled' => false,
			'class' => '',
			'css' => '',
			'placeholder' => '',
			'type' => 'text',
			'desc_tip' => false,
			'description' => '',
			'custom_attributes' => [],
		);

		$data = wp_parse_args($data, $defaults);

		ob_start();
		?>

		<div class="input-group">
			<input placeholder="<?php echo wp_kses_post( $data['title'] ); ?>" aria-label="<?php echo wp_kses_post( $data['title'] ); ?>" class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( $this->get_field_data( $key ) ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); ?>>
			<button type="submit" class="btn btn-outline-secondary"><?php echo wp_kses_post( $data['button'] ); ?></button>
		</div>
		<?php

		return ob_get_clean();
	}
}