<?php namespace Wsklad\Admin;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Wsklad\Abstracts\ScreenAbstract;
use Wsklad\Exceptions\Exception;
use Wsklad\Exceptions\RuntimeException;
use Wsklad\Traits\SectionsTrait;
use Wsklad\Traits\UtilityTrait;

/**
 * Tools
 *
 * @package Wsklad\Admin
 */
final class Tools
{
	use SingletonTrait;
	use UtilityTrait;
	use SectionsTrait;

	/**
	 * @var array All available tools
	 */
	public $tools = [];

	/**
	 * @var string Current tool id
	 */
	private $current_tool_id = '';

	/**
	 * Tools constructor.
	 */
	public function __construct()
	{
		$this->init();
	}

	/**
	 * Initialized
	 *
	 * @throws RuntimeException
	 */
	public function init()
	{
		try
		{
			$tools = wsklad()->tools()->get();
			$this->tools = $tools;
		}
		catch(Exception $exception){}

		$this->initCurrentId();
	}

	/**
	 * @return bool
	 */
	protected function initCurrentId()
	{
		$tool_id = wsklad()->getVar($_GET['tool_id'], '');

		if(!empty($tool_id) && array_key_exists($tool_id, $this->tools))
		{
			$this->setCurrentToolId($tool_id);
			return true;
		}

		return false;
	}

	/**
	 * @return string
	 */
	public function getCurrentToolId()
	{
		return $this->current_tool_id;
	}

	/**
	 * @param string $current_tool_id
	 */
	public function setCurrentToolId($current_tool_id)
	{
		$this->current_tool_id = $current_tool_id;
	}

	/**
	 * Output tools table
	 *
	 * @return void
	 */
	public function route()
	{
		add_action('wsklad_admin_show', [$this, 'wrapHeader'], 3);
		add_action('wsklad_admin_show', [$this, 'wrapSections'], 7);
		add_action('wsklad_admin_show', [$this, 'output'], 10);

		wsklad()->views()->getView('wrap.php');
	}

	/**
	 * Output tools table
	 *
	 * @return void
	 */
	public function output()
	{
		if(empty($this->tools))
		{
			wsklad()->views()->getView('tools/empty.php');
			return;
		}

		if($this->getCurrentToolId() !== '')
		{
			$tool = $this->tools[$this->getCurrentToolId()];

			$args =
				[
					'id' => $this->getCurrentToolId(),
					'name' => $tool->getName(),
					'description' => $tool->getDescription(),
					'back_url' => $this->utilityAdminToolsGetUrl(),
					'object' => $tool,
				];

			wsklad()->views()->getView('tools/single.php', $args);

			return;
		}

		$args['object'] = $this;

		wsklad()->views()->getView('tools/all.php', $args);
	}

	/**
	 * Sections
	 */
	public function wrapSections()
	{
		wsklad()->views()->getView('tools/sections.php');
	}

	/**
	 * Error
	 */
	public function wrapError()
	{
		wsklad()->views()->getView('tools/error.php');
	}

	/**
	 * Header
	 */
	public function wrapHeader()
	{
		wsklad()->views()->getView('tools/header.php');
	}
}