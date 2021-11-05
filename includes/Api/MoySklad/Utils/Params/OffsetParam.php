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
 * Class OffsetParam
 *
 * @package Wsklad\Api\MoySklad\Utils\Params
 */
class OffsetParam extends ApiParam
{
	/**
	 * @var int
	 */
	public $value;

	/**
	 * Offset constructor.
	 *
	 * @param $offset
	 */
	public function __construct($offset)
	{
		parent::__construct(self::OFFSET_PARAM);

		$this->value = $offset;
	}

	/**
	 * @param int $value
	 *
	 * @return OffsetParam|null
	 */
	public static function offset($value)
	{
		return new OffsetParam($value);
	}

	/**
	 * @param int $offset
	 *
	 * @return OffsetParam
	 */
	public static function eq($offset)
	{
		return new self($offset);
	}

	/**
	 * @return string
	 */
	public function render($host)
	{
		return sprintf('%d', $this->value);
	}
}
