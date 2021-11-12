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
 * Class ApiParam
 *
 * @package Wsklad\Api\MoySklad\Utils
 */
abstract class ApiParam
{
	const CONDITIONS =
	[
		'eq' => '=',
		'neq' => '!=',
		'gt' => '>',
		'lt' => '<',
		'gte' => '>=',
		'lte' => '<=',
		'like' => '~',
		'prefix' => '~=',
		'postfix' => '=~',
	];

	/**
	 * Filters
	 */
	const FILTER_PARAM = 'filter';
	const EXPAND = 'expand';
	const LIMIT_PARAM = 'limit';
	const OFFSET_PARAM = 'offset';
	const SEARCH_PARAM = 'search';
	const ORDER_PARAM = 'order';

	/**
	 * Separators
	 */
	const PARAM_TYPE_SEPARATOR =
	[
		self::FILTER_PARAM => ';',
		self::ORDER_PARAM => ';',
		self::EXPAND => ',',
	];

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * ApiParam constructor.
	 *
	 * @param string $type
	 */
	public function __construct($type)
	{
		$this->type = $type;
	}

	/**
	 * @param string $prop
	 * @return string|null
	 */
	public function __get($prop)
	{
		return $prop === 'type' ? $this->$prop : null;
	}

	/**
	 * @param $prop
	 * @return bool
	 */
	public function __isset($prop)
	{
		return $prop === 'type';
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function __set($key, $value)
	{
		$this->$key = $value;
	}

	/**
	 * @param string $paramType
	 * @param ApiParam[] $params
	 * @param $host
	 *
	 * @return string
	 */
	public static function renderStringQueryFromList($paramType, $params, $host = '')
	{
		$filteredParams = array_filter
		(
			$params, function (ApiParam $param) use ($paramType)
			{
				if ($param->type == $paramType)
				{
					return true;
				}
				return false;
			}
		);

		$stringsOfParams = array_map
		(
			function(ApiParam $param) use ($host)
			{
				return $param->render($host);
			},
			$filteredParams
		);

		if(array_key_exists($paramType, static::PARAM_TYPE_SEPARATOR))
		{
			return implode(self::PARAM_TYPE_SEPARATOR[$paramType], $stringsOfParams);
		}

		return current($stringsOfParams);
	}

	/**
	 * @param $host
	 *
	 * @return string
	 */
	abstract protected function render($host);
}
