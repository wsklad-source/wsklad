<?php namespace Wsklad\Admin\Settings;

defined('ABSPATH') || exit;

use Exception;
use Wsklad\Settings\MainSettings;

/**
 * Class MainForm
 *
 * @package Wsklad\Admin\Settings
 */
class MainForm extends Form
{
	/**
	 * MainForm constructor.
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->set_id('settings-main');
		$this->setSettings(new MainSettings());

		add_filter('wsklad_' . $this->get_id() . '_form_load_fields', [$this, 'init_form_fields_tecodes'], 10);
		add_filter('wsklad_' . $this->get_id() . '_form_load_fields', [$this, 'init_fields_accounts'], 10);
		add_filter('wsklad_' . $this->get_id() . '_form_load_fields', [$this, 'init_fields_technical'], 10);
		add_filter('wsklad_' . $this->get_id() . '_form_load_fields', [$this, 'init_fields_api_moysklad'], 10);

		$this->init();
	}

	/**
	 * Add fields for MoySklad
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_api_moysklad($fields)
	{
		$fields['api_moysklad_title'] =
		[
			'title' => __('API MoySklad', 'wsklad'),
			'type' => 'title',
			'description' => __('Used for API connections.', 'wsklad'),
		];

		$fields['api_moysklad_host'] =
		[
			'title' => __('Host', 'wsklad'),
			'type' => 'text',
			'description' => __('This host is used for API connection. If the host is unknown, use the value: online.moysklad.ru', 'wsklad'),
			'default' => 'online.moysklad.ru',
			'css' => 'min-width: 255px;',
		];

		$fields['api_moysklad_force_https'] =
		[
			'title' => __('Force requests over HTTPS', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Enable HTTPS enforcement for requests to the MoySklad API?', 'wsklad'),
			'description' => __('If enabled, all API requests from the site to MoySklad will be made over the secure HTTPS protocol.', 'wsklad'),
			'default' => 'yes'
		];

		$fields['api_moysklad_timeout'] =
		[
			'title' => __('Timeout', 'wsklad'),
			'type' => 'text',
			'description' => __('This timeout is used for API connection. If the timeout is unknown, use the value: 30', 'wsklad'),
			'default' => '30',
			'css' => 'min-width: 111px;',
		];

		return $fields;
	}

	/**
	 * Add fields for tecodes
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_form_fields_tecodes($fields)
	{
		$buy_url = esc_url('https://wsklad.ru/market/code');

		$fields['tecodes'] =
		[
			'title' => __('Support', 'wsklad'),
			'type' => 'title',
			'description' => sprintf
			(
				'%s %s <a target="_blank" href="%s">%s</a>. %s',
				__('If there is no code to support, bug fixes and plugin updates with new features will not be released.', 'wsklad'),
				__('The code can be obtained from the plugin website:', 'wsklad'),
				$buy_url,
				$buy_url,
				__('Users with active codes participate in the development of integration with Moy Sklad, they have a connection with developers and other additional features.', 'wsklad')
			),
		];

		if(wsklad()->tecodes()->is_valid())
		{
			$fields['tecodes_status'] =
			[
				'title' => __('Status', 'wsklad'),
				'type' => 'tecodes_status',
				'class' => 'p-2',
				'description' => __('Support code activated. To activate another code, you can enter it again.', 'wsklad'),
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
				'%s <br /> %s <b>%s</b>',
				__('If enter the correct code, the current environment will be activated. Enter the code only on the actual workstation.', 'wsklad'),
				__('Current license API status:', 'wsklad'),
				wsklad()->tecodes()->api_get_status()
			),
			'default' => ''
		];

		return $fields;
	}

	/**
	 * Add fields for Accounts
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_accounts($fields): array
	{
		$fields['accounts_title'] =
		[
			'title' => __('Accounts', 'wsklad'),
			'type' => 'title',
			'description' => __('Some settings for the accounts.', 'wsklad'),
		];

		$fields['accounts_test_before_add'] =
		[
			'title' => __('Test connection before add', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Enable data validation to connect to Moy Sklad before adding?', 'wsklad'),
			'description' => __('If enabled, then when connecting accounts from Moy Sklad, they will be checked for validity by a test connection.', 'wsklad'),
			'default' => 'yes'
		];

		$fields['accounts_unique_name'] =
		[
			'title' => __('Unique names', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Require unique names for accounts?', 'wsklad'),
			'description' => __('If enabled, will need to provide unique names for the accounts.', 'wsklad'),
			'default' => 'yes'
		];

		$fields['accounts_show_per_page'] =
		[
			'title' => __('Number in the list', 'wsklad'),
			'type' => 'text',
			'description' => __('The number of displayed accounts on one page.', 'wsklad'),
			'default' => 10,
			'css' => 'min-width: 20px;',
		];

		$fields['accounts_draft_delete'] =
		[
			'title' => __('Deleting drafts without trash', 'wsklad'),
			'type' => 'checkbox',
			'label' => __('Enable deleting drafts without placing them in the trash?', 'wsklad'),
			'description' => __('If enabled, accounts for connections in the draft status will be deleted without being added to the basket.', 'wc1c'),
			'default' => 'yes'
		];

		return $fields;
	}


	/**
	 * Add for Technical
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_technical($fields)
	{
		$fields['technical_title'] =
			[
				'title' => __('Technical settings', 'wsklad'),
				'type' => 'title',
				'description' => __('Used to set up the environment.', 'wsklad'),
			];

		$fields['php_max_execution_time'] =
			[
				'title' => __('Maximum time for execution PHP', 'wsklad'),
				'type' => 'text',
				'description' => sprintf
				(
					'%s <br /> %s <b>%s</b> <br /> %s',
					__('Value is seconds. wsklad will run until a time limit is set.', 'wsklad'),
					__('Server value:', 'wsklad'),
					wsklad()->environment()->get('php_max_execution_time'),
					__('If specify 0, the time limit will be disabled. Specifying 0 is not recommended, it is recommended not to exceed the server limit.', 'wsklad')
				),
				'default' => wsklad()->environment()->get('php_max_execution_time'),
				'css' => 'min-width: 100px;',
			];

		$fields['php_post_max_size'] =
			[
				'title' => __('Maximum request size', 'wsklad'),
				'type' => 'text',
				'description' => __('The setting must not take a size larger than specified in the server settings.', 'wsklad'),
				'default' => wsklad()->environment()->get('php_post_max_size'),
				'css' => 'min-width: 100px;',
			];

		return $fields;
	}


	/**
	 * Generate Tecodes data HTML
	 *
	 * @param string $key Field key.
	 * @param array  $data Field data.
	 *
	 * @return string
	 */
	public function generate_tecodes_status_html($key, $data)
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

		$local = wsklad()->tecodes()->get_local_code();
		$local_data = wsklad()->tecodes()->get_local_code_data($local);

		ob_start();

		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); ?></label>
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
				<?php echo $this->get_description_html($data); // WPCS: XSS ok.?>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate Tecodes Text Input HTML
	 *
	 * @param string $key Field key.
	 * @param array  $data Field data.
	 *
	 * @return string
	 */
	public function generate_tecodes_text_html($key, $data)
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
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); ?></label>
			</th>
			<td class="forminp">
				<div class="input-group">
					<input class="form-control input-text regular-input <?php echo esc_attr($data['class']); ?>"
					       type="<?php echo esc_attr($data['type']); ?>" name="<?php echo esc_attr($field_key); ?>"
					       id="<?php echo esc_attr($field_key); ?>" style="<?php echo esc_attr($data['css']); ?>"
					       value="<?php echo esc_attr($this->get_field_data($key)); ?>"
					       placeholder="<?php echo esc_attr($data['placeholder']); ?>" <?php disabled($data['disabled'], true); ?> <?php echo $this->get_custom_attribute_html($data); // WPCS: XSS ok.
					?> />
					<button name="save" class="btn btn-primary" type="submit" value="<?php _e('Activate', 'wsklad') ?>"><?php _e('Activate', 'wsklad') ?></button>
				</div>
				<?php echo $this->get_description_html($data); // WPCS: XSS ok.?>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Validate tecodes code
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return string
	 */
	public function validate_tecodes_code_field($key, $value)
	{
		if($value === '')
		{
			return '';
		}

		$value_valid = explode('-', $value);
		if('WSKLAD' !== strtoupper(reset($value_valid)))
		{
			wsklad()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => __('The support code is invalid. Enter the correct code.', 'wsklad')
				]
			);
			return '';
		}

		wsklad()->tecodes()->delete_local_code();
		wsklad()->tecodes()->set_code($value);

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
		}
		else
		{
			wsklad()->admin()->notices()->create
			(
				[
					'type' => 'info',
					'data' => __('Support code activated successfully. Reload the page to display.', ('wsklad'))
				]
			);
		}

		return '';
	}
}