<?php namespace Wsklad\Traits;
/**
 * Trait Singleton
 *
 * @package Wsklad\Traits
 */
trait Singleton
{
	/**
	 * All initialized instances
	 *
	 * @var array
	 */
	private static $instances = [];

	/**
	 * Get instance
	 *
	 * @return self
	 */
	public static function instance()
	{
		if(!isset(self::$instances[static::class]))
		{
			self::$instances[static::class] = new static;
		}

		return self::$instances[static::class];
	}
}