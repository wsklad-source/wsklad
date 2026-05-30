<?php declare(strict_types=1);

namespace Frescoref\Woplucore\Utils;

use Frescoref\Woplucore\AssetsManager;
use Frescoref\Woplucore\Contracts\Noncesable;

/**
 * Class Assets
 *
 * @package Frescoref\Woplucore\Utils
 * @since 1.0.0
 */
class Assets
{
    /**
     * Resolved manager instance.
     *
     * @var AssetsManager|null
     */
    private static $instance = null;

    /**
     * Configure the assets manager with prefix and optional nonce provider.
     *
     * @param string $prefix Base handle prefix (e.g., 'my-plugin-').
     * @param Noncesable|null $nonces Woplucore nonce provider.
     * @return void
     */
    public static function configure(string $prefix = '', ?Noncesable $nonces = null): void
    {
        self::$instance = new AssetsManager($prefix, $nonces);
    }

    /**
     * Resolve underlying manager instance. Creates default fallback if not configured.
     *
     * @return AssetsManager Resolved assets manager.
     */
    private static function instance(): AssetsManager
    {
        if (null === self::$instance) {
            self::$instance = new AssetsManager('');
        }
        return self::$instance;
    }

    /** {@inheritDoc AssetsManager::script()} */
    public static function script(): \Frescoref\Woplucore\Contracts\Scriptable { return self::instance()->script(); }

    /** {@inheritDoc AssetsManager::style()} */
    public static function style(): \Frescoref\Woplucore\Contracts\Styleable { return self::instance()->style(); }

    /** {@inheritDoc AssetsManager::prefix()} */
    public static function prefix(string $prefix): self { self::instance()->prefix($prefix); return new self(); }

    /** {@inheritDoc AssetsManager::when()} */
    public static function when(callable $condition): self { self::instance()->when($condition); return new self(); }

    /** {@inheritDoc AssetsManager::unless()} */
    public static function unless(callable $condition): self { self::instance()->unless($condition); return new self(); }

    /** {@inheritDoc AssetsManager::isRegistered()} */
    public static function isRegistered(string $handle): bool { return self::instance()->isRegistered($handle); }

    /** {@inheritDoc AssetsManager::isEnqueued()} */
    public static function isEnqueued(string $handle): bool { return self::instance()->isEnqueued($handle); }

    /** {@inheritDoc AssetsManager::flush()} */
    public static function flush(): void { self::instance()->flush(); }
}