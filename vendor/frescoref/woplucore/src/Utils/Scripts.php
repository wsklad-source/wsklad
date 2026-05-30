<?php declare(strict_types=1);

namespace Frescoref\Woplucore\Utils;

use Frescoref\Woplucore\Contracts\Noncesable;
use Frescoref\Woplucore\ScriptsManager;

/**
 * Class Scripts
 *
 * @package Frescoref\Woplucore\Utils
 * @since 1.0.0
 */
class Scripts
{
    /**
     * Resolved manager instance.
     *
     * @var ScriptsManager|null
     */
    private static $instance = null;

    /**
     * Configure the scripts manager with prefix and optional nonce provider.
     *
     * @param string          $prefix Handle prefix.
     * @param Noncesable|null $nonces Woplucore nonce provider.
     * @return void
     */
    public static function configure(string $prefix = '', ?Noncesable $nonces = null): void
    {
        self::$instance = new ScriptsManager($prefix, $nonces);
    }

    /**
     * Resolve underlying manager instance. Creates default fallback if not configured.
     *
     * @return ScriptsManager Resolved scripts manager.
     */
    private static function instance(): ScriptsManager
    {
        if (null === self::$instance) {
            self::$instance = new ScriptsManager('');
        }
        return self::$instance;
    }

    /** {@inheritDoc ScriptsManager::register()} */
    public static function register(string $handle, string $src, array $deps = [], ?string $version = null, bool $inFooter = true): self { self::instance()->register($handle, $src, $deps, $version, $inFooter); return new self(); }

    /** {@inheritDoc ScriptsManager::enqueue()} */
    public static function enqueue(string $handle): self { self::instance()->enqueue($handle); return new self(); }

    /** {@inheritDoc ScriptsManager::remove()} */
    public static function remove(string $handle): self { self::instance()->remove($handle); return new self(); }

    /** {@inheritDoc ScriptsManager::async()} */
    public static function async(string $handle): self { self::instance()->async($handle); return new self(); }

    /** {@inheritDoc ScriptsManager::defer()} */
    public static function defer(string $handle): self { self::instance()->defer($handle); return new self(); }

    /** {@inheritDoc ScriptsManager::strategy()} */
    public static function strategy(string $handle, string $strategy): self { self::instance()->strategy($handle, $strategy); return new self(); }

    /** {@inheritDoc ScriptsManager::data()} */
    public static function data(string $handle, array $data, string $name = 'wp_data'): self { self::instance()->data($handle, $data, $name); return new self(); }

    /** {@inheritDoc ScriptsManager::nonce()} */
    public static function nonce(string $handle, string $action, string $var = 'nonce'): self { self::instance()->nonce($handle, $action, $var); return new self(); }

    /** {@inheritDoc ScriptsManager::inline()} */
    public static function inline(string $handle, string $code, string $position = 'after', int $priority = 10): self { self::instance()->inline($handle, $code, $position, $priority); return new self(); }

    /** {@inheritDoc ScriptsManager::addDeps()} */
    public static function addDeps(string $handle, array $deps): self { self::instance()->addDeps($handle, $deps); return new self(); }

    /** {@inheritDoc ScriptsManager::setDeps()} */
    public static function setDeps(string $handle, array $deps): self { self::instance()->setDeps($handle, $deps); return new self(); }

    /** {@inheritDoc ScriptsManager::translations()} */
    public static function translations(string $handle, string $domain, string $path = ''): self { self::instance()->translations($handle, $domain, $path); return new self(); }

    /** {@inheritDoc ScriptsManager::when()} */
    public static function when(callable $condition): self { self::instance()->when($condition); return new self(); }

    /** {@inheritDoc ScriptsManager::unless()} */
    public static function unless(callable $condition): self { self::instance()->unless($condition); return new self(); }

    /** {@inheritDoc ScriptsManager::autoVersion()} */
    public static function autoVersion(string $src): ?string { return self::instance()->autoVersion($src); }
}