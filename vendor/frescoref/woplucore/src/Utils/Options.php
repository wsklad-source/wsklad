<?php declare(strict_types=1);

namespace Frescoref\Woplucore\Utils;

use Frescoref\Woplucore\OptionsManager;

/**
 * Class Options
 *
 * @package Frescoref\Woplucore\Utils
 * @since 1.0.0
 */
class Options
{
    /**
     * Resolved manager instance.
     *
     * @var OptionsManager|null
     */
    private static $instance = null;

    /**
     * Bind prefix for advanced features (auto-prefixing, batch ops, state checks).
     *
     * @param string $prefix Optional prefix for key scoping. Empty string disables prefixing.
     * @return void
     */
    public static function configure(string $prefix = ''): void
    {
        self::$instance = new OptionsManager($prefix);
    }

    /**
     * Resolve underlying manager instance. Creates default fallback if not configured.
     *
     * @return OptionsManager Resolved options manager.
     */
    private static function instance(): OptionsManager
    {
        if (null === self::$instance) {
            self::$instance = new OptionsManager('');
        }
        return self::$instance;
    }

    /** {@inheritDoc OptionsManager::get()} */
    public static function get(string $key, $default = null) { return self::instance()->get($key, $default); }

    /** {@inheritDoc OptionsManager::set()} */
    public static function set(string $key, $value, bool $autoload = false): bool { return self::instance()->set($key, $value, $autoload); }

    /** {@inheritDoc OptionsManager::delete()} */
    public static function delete(string $key): bool { return self::instance()->delete($key); }

    /** {@inheritDoc OptionsManager::has()} */
    public static function has(string $key): bool { return self::instance()->has($key); }

    /** {@inheritDoc OptionsManager::getMultiple()} */
    public static function getMultiple(array $keys, array $defaults = []): array { return self::instance()->getMultiple($keys, $defaults); }

    /** {@inheritDoc OptionsManager::setMultiple()} */
    public static function setMultiple(array $data): bool { return self::instance()->setMultiple($data); }

    /** {@inheritDoc OptionsManager::isEmpty()} */
    public static function isEmpty(string $key): bool { return self::instance()->isEmpty($key); }

    /** {@inheritDoc OptionsManager::toggle()} */
    public static function toggle(string $key): bool { return self::instance()->toggle($key); }

    /** {@inheritDoc OptionsManager::changeAutoload()} */
    public static function changeAutoload(string $key, bool $autoload = true): bool { return self::instance()->changeAutoload($key, $autoload); }

    /** {@inheritDoc OptionsManager::getPrefix()} */
    public static function getPrefix(): string { return self::instance()->getPrefix(); }

    /** {@inheritDoc OptionsManager::setPrefix()} */
    public static function setPrefix(string $prefix): void { self::instance()->setPrefix($prefix); }

    /** {@inheritDoc OptionsManager::isPrefixed()} */
    public static function isPrefixed(string $key): bool { return self::instance()->isPrefixed($key); }

    /** {@inheritDoc OptionsManager::stripPrefix()} */
    public static function stripPrefix(string $key): string { return self::instance()->stripPrefix($key); }

    /** {@inheritDoc OptionsManager::resolvePrefix()} */
    public static function resolvePrefix(string $key): string { return self::instance()->resolvePrefix($key); }
}