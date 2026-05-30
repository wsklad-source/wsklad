<?php declare(strict_types=1);

namespace Frescoref\Woplucore\Utils;

use Frescoref\Woplucore\StylesManager;

/**
 * Class Styles
 *
 * @package Frescoref\Woplucore\Utils
 * @since 1.0.0
 */
class Styles
{
    /**
     * Resolved manager instance.
     *
     * @var StylesManager|null
     */
    private static $instance = null;

    /**
     * Configure the styles manager with prefix.
     *
     * @param string $prefix Handle prefix.
     * @return void
     */
    public static function configure(string $prefix = ''): void
    {
        self::$instance = new StylesManager($prefix);
    }

    /**
     * Resolve underlying manager instance. Creates default fallback if not configured.
     *
     * @return StylesManager Resolved styles manager.
     */
    private static function instance(): StylesManager
    {
        if (null === self::$instance) {
            self::$instance = new StylesManager('');
        }
        return self::$instance;
    }

    /** {@inheritDoc StylesManager::register()} */
    public static function register(string $handle, string $src, array $deps = [], ?string $version = null, string $media = 'all'): self { self::instance()->register($handle, $src, $deps, $version, $media); return new self(); }

    /** {@inheritDoc StylesManager::enqueue()} */
    public static function enqueue(string $handle): self { self::instance()->enqueue($handle); return new self(); }

    /** {@inheritDoc StylesManager::remove()} */
    public static function remove(string $handle): self { self::instance()->remove($handle); return new self(); }

    /** {@inheritDoc StylesManager::inline()} */
    public static function inline(string $handle, string $css): self { self::instance()->inline($handle, $css); return new self(); }

    /** {@inheritDoc StylesManager::conditional()} */
    public static function conditional(string $handle, string $condition): self { self::instance()->conditional($handle, $condition); return new self(); }

    /** {@inheritDoc StylesManager::media()} */
    public static function media(string $handle, string $query): self { self::instance()->media($handle, $query); return new self(); }

    /** {@inheritDoc StylesManager::rtl()} */
    public static function rtl(string $handle, bool $enable = true): self { self::instance()->rtl($handle, $enable); return new self(); }

    /** {@inheritDoc StylesManager::addDeps()} */
    public static function addDeps(string $handle, array $deps): self { self::instance()->addDeps($handle, $deps); return new self(); }

    /** {@inheritDoc StylesManager::when()} */
    public static function when(callable $condition): self { self::instance()->when($condition); return new self(); }

    /** {@inheritDoc StylesManager::unless()} */
    public static function unless(callable $condition): self { self::instance()->unless($condition); return new self(); }

    /** {@inheritDoc StylesManager::autoVersion()} */
    public static function autoVersion(string $src): ?string { return self::instance()->autoVersion($src); }
}