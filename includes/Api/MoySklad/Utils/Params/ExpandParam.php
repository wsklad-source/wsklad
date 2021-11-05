<?php
/**
 * Namespace
 */
namespace Wsklad\Api\MoySklad\Utils\Params;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Class ExpandParam
 *
 * @package Wsklad\Api\MoySklad\Utils\Params
 */
class ExpandParam extends ApiParam
{
	/**
	 * @var Strings[] array
	 */
	public $fields = [];

	/**
	 * Extra-fields params constructor
	 *
	 * @param array $fields
	 */
	public function __construct($fields)
	{
		parent::__construct(self::EXPAND);

		$this->fields = $fields;
	}

	/**
	 * @param $fields
	 *
	 * @return ExpandParam|null
	 */
	public static function expand($fields)
	{
		if($fields === null || count($fields) === 0)
		{
			return null;
		}

		return new ExpandParam($fields);
	}

	/**
	 * @return array
	 */
	public function render($host)
	{
		return $this->fields;
	}
}
