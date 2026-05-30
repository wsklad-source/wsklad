<?php declare(strict_types=1);

namespace Frescoref\Woplucore;

use Frescoref\Woplucore\Contracts\Assetsable;
use Frescoref\Woplucore\Contracts\Scriptable;
use Frescoref\Woplucore\Contracts\Styleable;
use Frescoref\Woplucore\Contracts\Noncesable;

/**
 * Class AssetsManager
 *
 * @package Woplucore
 * @since 1.0.0
 */
class AssetsManager implements Assetsable
{
    /**
     * Current handle prefix.
     *
     * @var string
     */
    private $prefix = '';

    /**
     * Global condition applied to all subsequent script/style operations.
     *
     * @var callable|null
     */
    private $globalCondition = null;

    /**
     * Nonce provider for dependency injection.
     *
     * @var Noncesable|null
     */
    private $nonces;

    /**
     * Cached script builder instance.
     *
     * @var ScriptsManager|null
     */
    private $scriptBuilder;

    /**
     * Cached style builder instance.
     *
     * @var StylesManager|null
     */
    private $styleBuilder;

    /**
     * Constructor.
     *
     * @param string $prefix Base handle prefix (e.g., 'my-plugin-').
     * @param Noncesable|null $nonces Woplucore nonce provider.
     */
    public function __construct(string $prefix = '', ?Noncesable $nonces = null)
    {
        $this->prefix = '' !== $prefix ? \rtrim($prefix, '-_') . '-' : '';
        $this->nonces = $nonces;
    }

    // =========================================================================
    // FACTORY & BUILDERS
    // =========================================================================

    /** {@inheritDoc} */
    public function script(): Scriptable
    {
        if (null === $this->scriptBuilder) {
            $this->scriptBuilder = new ScriptsManager($this->prefix, $this->nonces);
        }
        return $this->applyGlobalCondition($this->scriptBuilder);
    }

    /** {@inheritDoc} */
    public function style(): Styleable
    {
        if (null === $this->styleBuilder) {
            $this->styleBuilder = new StylesManager($this->prefix);
        }
        return $this->applyGlobalCondition($this->styleBuilder);
    }

    // =========================================================================
    // CONFIGURATION & CONDITIONS
    // =========================================================================

    /** {@inheritDoc} */
    public function prefix(string $prefix): self
    {
        $this->prefix = '' !== $prefix ? \rtrim($prefix, '-_') . '-' : '';
        // Rebuild instances to apply new prefix immediately
        $this->scriptBuilder = null;
        $this->styleBuilder  = null;
        return $this;
    }

    /** {@inheritDoc} */
    public function when(callable $condition): self
    {
        $this->globalCondition = $condition;
        return $this;
    }

    /** {@inheritDoc} */
    public function unless(callable $condition): self
    {
        return $this->when(function () use ($condition): bool {
            return ! (bool) \call_user_func($condition);
        });
    }

    // =========================================================================
    // INSPECTION & STATE
    // =========================================================================

    /** {@inheritDoc} */
    public function isRegistered(string $handle): bool
    {
        $fullHandle = $this->prefix . $handle;
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        return \wp_script_is($fullHandle, 'registered') || \wp_style_is($fullHandle, 'registered');
    }

    /** {@inheritDoc} */
    public function isEnqueued(string $handle): bool
    {
        $fullHandle = $this->prefix . $handle;
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        return \wp_script_is($fullHandle, 'enqueued') || \wp_style_is($fullHandle, 'enqueued');
    }

    /** {@inheritDoc} */
    public function flush(): void
    {
        $this->globalCondition = null;
        $this->scriptBuilder   = null;
        $this->styleBuilder    = null;
    }

    // =========================================================================
    // INTERNAL HELPERS
    // =========================================================================

    /**
     * Apply global condition to a builder instance.
     * Chains condition to the builder's fluent API before returning.
     *
     * @template T of Scriptable|Styleable
     * @param T $builder
     * @return T
     */
    private function applyGlobalCondition($builder)
    {
        if (null !== $this->globalCondition) {
            $builder->when($this->globalCondition);
        }
        return $builder;
    }
}