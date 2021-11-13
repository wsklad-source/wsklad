<?php
/**
 * Namespace
 */
namespace Wsklad\MoySklad\Utils\Params;

/**
 * Only WordPress
 */
defined('ABSPATH') || exit;

/**
 * Class OrderParam
 *
 * @package Wsklad\MoySklad\Utils\Params
 */
class OrderParam extends ApiParam
{
	/**
	 * @var string
	 */
	public $field;

	/**
	 * @var string asc - По возрастанию, desc - по убыванию
	 */
	public $direction;

	/**
	 * Order constructor.
	 *
	 * @param string $field
	 * @param string $direction
	 */
	public function __construct($field = '', $direction = '')
	{
		parent::__construct(self::ORDER_PARAM);

		$this->field = $field;
		$this->direction = $direction;
	}

	/**
	 * @param int $value
	 *
	 * @return OrderParam
	 */
	public static function order($value, $direction)
	{
		return new OrderParam($value, $direction);
	}

	/**
	 * @param string $field
	 *
	 * @return OrderParam
	 */
	public static function asc($field)
	{
		return new self($field, __FUNCTION__);
	}

	/**
	 * @param string $field
	 *
	 * @return OrderParam
	 */
	public static function desc($field)
	{
		return new self($field, __FUNCTION__);
	}

	/**
	 * @return string
	 */
	public function render($host)
	{
		return sprintf('%s,%s', $this->field, $this->direction);
	}
}
