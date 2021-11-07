<?php
/**
 * Namespace
 */
namespace Wsklad\Settings;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Dependencies
 */
use Wsklad\Abstracts\SettingsAbstract;

/**
 * Class OtherSettings
 *
 * @package Wsklad\Settings
 */
class OtherSettings extends SettingsAbstract
{
	/**
	 * Main constructor.
	 */
	public function __construct()
	{
		$this->setOptionName('other');
	}
}