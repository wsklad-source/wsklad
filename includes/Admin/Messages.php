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
	 * Add messages
	 *
	 * @param $type
	 * @param $message
	 */
	public function addMessage($type, $message)
	{
		$this->data[] =
			[
				'type' => $type,
				'message' => $message
			];
	}

	/**
	 * Set messages
	 *
	 * @param $messages
	 */
	public function setMessages($messages)
	{
		$this->data = $messages;
	}

	/**
	 * Get messages
	 *
	 * @return array
	 */
	public function getMessages()
	{
		return $this->data;
	}

	/**
	 * Show messages in admin
	 */
	public function printMessages()
	{
		$messages = $this->getMessages();

		if(count($messages) > 0)
		{
			foreach($messages as $message_key => $message_data)
			{
				echo $this->formatMessage($message_data['type'], $message_data['message']);
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
	public function formatMessage($type, $message, $args = [])
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