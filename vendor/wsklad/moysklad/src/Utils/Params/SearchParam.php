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
 * Class SearchParam
 *
 * @package Wsklad\MoySklad\Utils\Params
 */
class SearchParam extends ApiParam
{
	/**
	 * @var string
	 */
	public $value;

	/**
	 * SearchParam constructor.
	 *
	 * @param string $value
	 */
	public function __construct($value)
	{
		parent::__construct(self::SEARCH_PARAM);

		$this->value = $value;
	}

	/**
	 * @param $value
	 *
	 * @return SearchParam|null
	 */
	public static function search($value)
	{
		if($value === null)
		{
			return null;
		}

		return new SearchParam($value);
	}

	/**
	 * @param string $value
	 *
	 * @return SearchParam
	 */
	public static function eq($value)
	{
		return new self($value);
	}

	/**
	 * @return string
	 */
	public function render($host)
	{
		return sprintf('%s', $this->value);
	}
}
