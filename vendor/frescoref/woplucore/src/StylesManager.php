<?php declare(strict_types=1);

namespace Frescoref\Woplucore;

use Frescoref\Woplucore\Contracts\Styleable;

/**
 * Class StylesManager
 *
 * @package Woplucore
 * @since 1.0.0
 */
class StylesManager implements Styleable
{
    /**
     * Handle prefix applied to all operations.
     *
     * @var string
     */
    private $prefix;

    /**
     * Pending condition callback for the next chained operation.
     * Resets automatically after evaluation.
     *
     * @var callable|null
     */
    private $nextCondition;

    /**
     * Constructor.
     *
     * @param string $prefix Handle prefix (e.g., 'my-plugin-').
     */
    public function __construct(string $prefix = '')
    {
        $this->prefix = '' !== $prefix ? \rtrim($prefix, '-_') . '-' : '';
        $this->nextCondition = null;
    }

    // =========================================================================
    // REGISTRATION & ENQUEUE
    // =========================================================================

    /** {@inheritDoc} */
    public function register(string $handle, string $src, array $deps = [], ?string $version = null, string $media = 'all'): self
    {
        if (!$this->shouldExecute()) {
            return $this;
        }

        $handle  = $this->prefix . $handle;
        $version = $version ?? $this->autoVersion($src);

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \wp_register_style($handle, $src, $deps, $version, $media);
        return $this;
    }

    /** {@inheritDoc} */
    public function enqueue(string $handle): self
    {
        if (!$this->shouldExecute()) {
            return $this;
        }

        $handle = $this->prefix . $handle;

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \wp_enqueue_style($handle);
        return $this;
    }

    /** {@inheritDoc} */
    public function remove(string $handle): self
    {
        $handle = $this->prefix . $handle;
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \wp_dequeue_style($handle);
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \wp_deregister_style($handle);
        return $this;
    }

    // =========================================================================
    // INLINE & ATTRIBUTE CONTROL
    // =========================================================================

    /** {@inheritDoc} */
    public function inline(string $handle, string $css): self
    {
        if (!$this->shouldExecute()) {
            return $this;
        }

        $handle = $this->prefix . $handle;
        // Note: WP core does not support position/priority for inline styles.
        // Content is appended directly after the enqueued stylesheet tag.
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \wp_add_inline_style($handle, $css);
        return $this;
    }

    /** {@inheritDoc} */
    public function conditional(string $handle, string $condition): self
    {
        if (!$this->shouldExecute()) {
            return $this;
        }

        $handle = $this->prefix . $handle;
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \wp_style_add_data($handle, 'conditional', $condition);
        return $this;
    }

    /** {@inheritDoc} */
    public function media(string $handle, string $query): self
    {
        if (!$this->shouldExecute()) {
            return $this;
        }

        $handle = $this->prefix . $handle;
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \wp_style_add_data($handle, 'media', $query);
        return $this;
    }

    /** {@inheritDoc} */
    public function rtl(string $handle, bool $enable = true): self
    {
        if (!$this->shouldExecute()) {
            return $this;
        }

        $handle = $this->prefix . $handle;
        // WP accepts bool or 'rtl'/'replace'. We pass strict boolean for clarity.
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \wp_style_add_data($handle, 'rtl', $enable);
        return $this;
    }

    // =========================================================================
    // DEPENDENCY & UTILITIES
    // =========================================================================

    /** {@inheritDoc} */
    public function addDeps(string $handle, array $deps): self
    {
        if (!$this->shouldExecute()) {
            return $this;
        }

        $handle    = $this->prefix . $handle;
        $wpStyles  = \wp_styles();
        $registered = $wpStyles->registered[$handle] ?? null;
        $currentDeps = $registered ? ($registered->deps ?: []) : [];
        $merged    = \array_values(\array_unique(\array_merge($currentDeps, $deps)));

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \wp_style_add_data($handle, 'deps', $merged);
        return $this;
    }

    /** {@inheritDoc} */
    public function when(callable $condition): self
    {
        $this->nextCondition = $condition;
        return $this;
    }

    /** {@inheritDoc} */
    public function unless(callable $condition): self
    {
        // Delegates to when(), zero logic duplication
        return $this->when(function () use ($condition): bool {
            return ! (bool) \call_user_func($condition);
        });
    }

    /** {@inheritDoc} */
    public function autoVersion(string $src): ?string
    {
        // Skip external URLs or protocol-relative paths
        if (\preg_match('#^https?://|^//#', $src)) {
            return null;
        }

        $path = $this->resolveToAbsolutePath($src);
        if (null === $path || !\is_readable($path)) {
            return null;
        }

        $mtime = \filemtime($path);
        return false === $mtime ? null : (string) $mtime;
    }

    // =========================================================================
    // INTERNAL HELPERS
    // =========================================================================

    /**
     * Check if current operation should execute based on pending condition.
     *
     * @return bool
     */
    private function shouldExecute(): bool
    {
        if (null === $this->nextCondition) {
            return true;
        }

        $result = (bool) \call_user_func($this->nextCondition);
        $this->nextCondition = null; // Reset after single evaluation
        return $result;
    }

    /**
     * Resolve URL or relative path to absolute filesystem path for filemtime().
     * Checks common WP directories in priority order.
     *
     * @param string $src Relative or absolute path.
     * @return string|null Absolute readable path or null.
     */
    private function resolveToAbsolutePath(string $src): ?string
    {
        // Already absolute and exists
        if (\file_exists($src)) {
            return \realpath($src);
        }

        // Normalize slashes for cross-platform compatibility
        $normalized = \str_replace(['\\', '//'], '/', $src);
        $trimmed    = \ltrim($normalized, '/');

        // Priority-ordered WP base directories
        $bases = [];
        if (\defined('WP_PLUGIN_DIR')) { $bases[] = \WP_PLUGIN_DIR; }
        if (\defined('WPMU_PLUGIN_DIR')) { $bases[] = \WPMU_PLUGIN_DIR; }
        if (\defined('WP_CONTENT_DIR')) { $bases[] = \WP_CONTENT_DIR; }
        if (\defined('ABSPATH')) { $bases[] = \ABSPATH; }

        foreach ($bases as $base) {
            $path = \rtrim($base, '/') . '/' . $trimmed;
            if (\file_exists($path) && \is_readable($path)) {
                return \realpath($path);
            }
        }

        return null;
    }
}