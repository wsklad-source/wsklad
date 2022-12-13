<?php namespace Digiom\ApiMoySklad\Utils\Params;

defined('ABSPATH') || exit;

/**
 * Class OffsetParam
 *
 * @package Digiom\ApiMoySklad\Utils\Params
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
	public static function eq(int $offset)
	{
		return new self($offset);
	}

	/**
	 * @return string
	 */
	public function render($host): string
	{
		return sprintf('%d', $this->value);
	}
}
