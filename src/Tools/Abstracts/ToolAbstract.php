<?php namespace Wsklad\Tools\Abstracts;

defined('ABSPATH') || exit;

use Wsklad\Exceptions\Exception;

/**
 * ToolAbstract
 *
 * @package Wsklad\Tools
 */
abstract class ToolAbstract
{
	/**
	 * @var string Unique tool id
	 */
	private $id = '';

	/**
	 * @var string Name
	 */
	private $name = '';

	/**
	 * @var string Description
	 */
	private $description = '';

	/**
	 * @var string Tool Author
	 */
	private $author = 'WSKLAD team';

	/**
	 * @throws Exception
	 *
	 * @return mixed
	 */
	abstract public function init();

	/**
	 * Set tool id
	 *
	 * @param $id
	 *
	 * @return $this
	 */
	public function setId($id)
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * Get tool id
	 *
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName(): string
    {
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName(string $name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getDescription(): string
    {
		return $this->description;
	}

	/**
	 * @param string $description
	 */
	public function setDescription(string $description)
	{
		$this->description = $description;
	}

	/**
	 * @return string
	 */
	public function getAuthor(): string
    {
		return $this->author;
	}

	/**
	 * @param string $author
	 */
	public function setAuthor(string $author)
	{
		$this->author = $author;
	}
}