<?php
/**
 * Namespace
 */
namespace Wsklad\Admin;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Wsklad\Traits\Singleton;

/**
 * Class Messages
 *
 * @package Wsklad\Admin
 */
class Messages
{
	/**
	 * Traits
	 */
	use Singleton;

	/**
	 * Current messages
	 *
	 * @var array
	 */
	private $data = [];

	/**
	 * Add admin messages
	 *
	 * @param $type
	 * @param $message
	 */
	public function add_message($type, $message)
	{
		$this->data[] =
			[
				'type' => $type,
				'message' => $message
			];
	}

	/**
	 * Set admin messages
	 *
	 * @param $messages
	 */
	public function set_messages($messages)
	{
		$this->data = $messages;
	}

	/**
	 * Get admin messages
	 *
	 * @return array
	 */
	public function get_messages()
	{
		return $this->data;
	}

	/**
	 * Show messages in admin
	 */
	public function print_messages()
	{
		$messages = $this->get_messages();

		if(count($messages) > 0)
		{
			foreach($messages as $message_key => $message_data)
			{
				echo $this->format_message($message_data['type'], $message_data['message']);
			}
		}
	}

	/**
	 * Format message to notice in admin
	 *
	 * @param $type
	 * @param $message
	 * @param $args
	 *  [
	 *      dismiss - true or false
	 *  ]
	 *
	 * @return string
	 */
	public function format_message($type, $message, $args = [])
	{
		if($type === 'error')
		{
			return '<div id="message" class="wsklad-error error updated notice is-dismissible"><p><strong>' . $message . '</strong></p><button class="notice-dismiss" type="button"><span class="screen-reader-text">' . __( 'Close', 'wsklad' ) . '</span></button></div>';
		}

		if($type === 'update')
		{
			return '<div id="message" class="wsklad-update update updated notice is-dismissible"><p><strong>' . $message . '</strong></p><button class="notice-dismiss" type="button"><span class="screen-reader-text">' . __( 'Close', 'wsklad' ) . '</span></button></div>';
		}

		return $message;
	}
}