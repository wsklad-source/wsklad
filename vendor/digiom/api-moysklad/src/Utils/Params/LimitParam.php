<?php namespace Digiom\ApiMoySklad\Utils\Params;

use InvalidArgumentException;

/**
 * Class LimitParam
 *
 * @package Digiom\ApiMoySklad\Utils\Params
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
    public static function eq(int $limit): LimitParam // todo: remove?
    {
        return new self($limit);
    }

	/**
	 * @param $host
	 *
	 * @return string
	 */
    public function render($host): string
    {
        return sprintf('%d', $this->value);
    }
}
