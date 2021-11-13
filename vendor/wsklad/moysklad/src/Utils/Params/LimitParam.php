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
 * Dependencies
 */
use InvalidArgumentException;

/**
 * Class LimitParam
 *
 * @package Wsklad\MoySklad\Utils\Params
 */
class LimitParam extends ApiParam
{
    const MIN_LIMIT = 1;
    const MAX_LIMIT = 1000;

    /**
     * @var int
     */
    public $value;

	/**
	 * LimitParam constructor.
	 *
	 * @param $limit
	 */
    public function __construct($limit)
    {
        if($limit < self::MIN_LIMIT || $limit > self::MAX_LIMIT)
        {
            throw new InvalidArgumentException('Allowed values range 1 - 1000');
        }

        parent::__construct(self::LIMIT_PARAM);

        $this->value = $limit;
    }

	/**
	 * @param int $limit
	 *
	 * @return LimitParam|null
	 */
	public static function expand($limit)
	{
		if($limit === null)
		{
			return null;
		}

		return new LimitParam($limit);
	}

    /**
     * @param int $limit
     *
     * @return LimitParam
     */
    public static function eq($limit) // todo: remove?
    {
        return new self($limit);
    }

    /**
     * @return string
     */
    public function render($host)
    {
        return sprintf('%d', $this->value);
    }
}
