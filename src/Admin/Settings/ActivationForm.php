<?php namespace Wsklad\Admin\Settings;

defined('ABSPATH') || exit;

use Wsklad\Exceptions\Exception;
use Wsklad\Settings\MainSettings;

/**
 * ActivationForm
 *
 * @package Wsklad\Admin
 */
class ActivationForm extends Form
{
	/**
	 * MainForm constructor.
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->setId('activation');
		$this->setSettings(new MainSettings());

		add_filter($this->prefix . '_' . $this->getId() . '_form_load_fields', [$this, 'init_form_fields_tecodes'], 10);

		$this->init();
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
			catch(\Exception $e)
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

        $code = $post_data['wsklad_activation_form_field_tecodes_code'] ?? '';

		$value_valid = explode('-', $code);
		if('WPWSKLAD' !== strtoupper(reset($value_valid)) && 'WSKLAD' !== strtoupper(reset($value_valid)))
		{
			wsklad()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => __('The code is invalid. Enter the correct code.', 'wsklad')
				]
			);
			return '';
		}

		wsklad()->tecodes()->delete_local_code();
		wsklad()->tecodes()->set_code($code);

		if(false === wsklad()->tecodes()->validate())
		{
			$errors = wsklad()->tecodes()->get_errors();

			if(is_array($errors))
			{
				foreach(wsklad()->tecodes()->get_errors() as $error_key => $error)
				{
					wsklad()->admin()->notices()->create
					(
						[
							'type' => 'error',
							'data' => $error
						]
					);
				}
			}

            return false;
		}

		wsklad()->admin()->notices()->create
		(
			[
				'type' => 'info',
				'data' => __('Code activated successfully.', ('wsklad'))
			]
		);

        return true;
	}

	/**
	 * Validate tecodes code
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return string
	 */
	public function validate_tecodes_code_field(string $key, string $value): string
	{
		return '';
	}

	/**
	 * Add fields for tecodes
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_form_fields_tecodes($fields): array
	{
		if(wsklad()->tecodes()->is_valid())
		{
			$fields['tecodes_status'] =
            [
                'title' => __('Status', 'wsklad'),
                'type' => 'tecodes_status',
                'class' => 'p-2',
                'default' => ''
            ];
		}

        $fields['tecodes_code'] =
        [
            'title' => __('Code for activation', 'wsklad'),
            'type' => 'tecodes_text',
            'class' => 'p-2',
            'description' => sprintf
            (
                '%s <b>%s</b><br /> <hr> %s <b>%s</b>',
                __('Enter the code only on the actual workstation.', 'wsklad'),
                __('If enter the correct code, the current environment will be activated.', 'wsklad'),
                __('Current activation API status:', 'wsklad'),
                esc_attr__(wsklad()->tecodes()->api_get_status(), 'wsklad')
            ),
            'default' => ''
        ];

		return $fields;
	}

	/**
	 * Generate Tecodes data HTML
	 *
	 * @param string $key Field key.
	 * @param array $data Field data.
	 *
	 * @return string
	 */
	public function generate_tecodes_status_html(string $key, array $data): string
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

		$local = wsklad()->tecodes()->get_local_code();
		$local_data = wsklad()->tecodes()->get_local_code_data($local);

		ob_start();

		?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->getTooltipHtml( $data ); ?></label>
            </th>
            <td class="forminp">
                <div class="wsklad-custom-metas">

		            <?php

                        if($local_data['code_date_expires'] === 'never')
                        {
                            $local_data['code_date_expires'] = __('never', 'wsklad');
                        }
                        else
                        {
	                        $local_data['code_date_expires'] = date_i18n(get_option('date_format'), $local_data['code_date_expires']);
                        }

                        printf
                        (
                                '%s: <b>%s</b> (%s %s)<br />%s: <b>%s</b><br />%s: <b>%s</b>',
                                __('Code ID', 'wsklad'),
                                $local_data['code_id'],
                                __('expires:', 'wsklad'),
                                $local_data['code_date_expires'] ,
                                __('Instance ID', 'wsklad'),
                                $local_data['instance_id'],
                                __('Domain', 'wsklad'),
                                $local_data['instance']['domain']
                        );
		            ?>

                </div>
				<?php echo wp_kses_post($this->getDescriptionHtml($data)); // WPCS: XSS ok.?>
            </td>
        </tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate Tecodes Text Input HTML
	 *
	 * @param string $key Field key.
	 * @param array $data Field data.
	 *
	 * @return string
	 */
	public function generate_tecodes_text_html(string $key, array $data): string
	{
		$field_key = $this->getPrefixFieldKey($key);
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
		<tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->getTooltipHtml( $data ); ?></label>
            </th>
			<td class="forminp">
                <div class="input-group">
                    <input class="form-control input-text regular-input <?php echo esc_attr($data['class']); ?>"
                    type="<?php echo esc_attr($data['type']); ?>" name="<?php echo esc_attr($field_key); ?>"
                    id="<?php echo esc_attr($field_key); ?>" style="<?php echo esc_attr($data['css']); ?>"
                    value="<?php echo esc_attr($this->getFieldData($key)); ?>"
                    placeholder="<?php echo esc_attr($data['placeholder']); ?>" <?php disabled($data['disabled'], true); ?> <?php echo $this->getCustomAttributeHtml($data); // WPCS: XSS ok.
                    ?> />
                    <button name="save" class="btn btn-primary" type="submit" value="<?php _e('Activate', 'wsklad') ?>"><?php _e('Activate', 'wsklad') ?></button>
                </div>
                <?php echo wp_kses_post($this->getDescriptionHtml($data)); // WPCS: XSS ok.?>
            </td>
		</tr>
		<?php

		return ob_get_clean();
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

		wsklad()->views()->getView('activation/form.php', $args);
	}
}