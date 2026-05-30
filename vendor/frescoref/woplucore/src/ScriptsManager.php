<?php declare(strict_types=1);

namespace Frescoref\Woplucore;

use Frescoref\Woplucore\Contracts\Noncesable;
use Frescoref\Woplucore\Contracts\Scriptable;

/**
 * Class ScriptsManager
 *
 * @package Woplucore
 * @since 1.0.0
 */
class ScriptsManager implements Scriptable
{
    /**
     * Handle prefix applied to all operations.
     *
     * @var string
     */
    private $prefix;

    /**
     * Nonce provider from Woplucore library.
     *
     * @var Noncesable|null
     */
    private $nonces;

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
     * @param Noncesable|null $nonces Woplucore nonce provider for secure token generation.
     */
    public function __construct(string $prefix = '', ?Noncesable $nonces = null)
    {
        $this->prefix = '' !== $prefix ? \rtrim($prefix, '-_') . '-' : '';
        $this->nonces = $nonces;
        $this->nextCondition = null;
    }

    // =========================================================================
    // REGISTRATION & ENQUEUE
    // =========================================================================

    /** {@inheritDoc} */
    public function register(string $handle, string $src, array $deps = [], ?string $version = null, bool $inFooter = true): self
    {
        if (!$this->shouldExecute()) {
            return $this;
        }

        $handle  = $this->prefix . $handle;
        $version = $version ?? $this->autoVersion($src);

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \wp_register_script($handle, $src, $deps, $version, $inFooter);
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
        \wp_enqueue_script($handle);
        return $this;
    }

    /** {@inheritDoc} */
    public function remove(string $handle): self
    {
        $handle = $this->prefix . $handle;
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \wp_dequeue_script($handle);
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \wp_deregister_script($handle);
        return $this;
    }

    // =========================================================================
    // EXECUTION FLAGS
    // =========================================================================

    /** {@inheritDoc} */
    public function async(string $handle): self
    {
        return $this->strategy($handle, 'async');
    }

    /** {@inheritDoc} */
    public function defer(string $handle): self
    {
        return $this->strategy($handle, 'defer');
    }

    /** {@inheritDoc} */
    public function strategy(string $handle, string $strategy): self
    {
        if (!$this->shouldExecute()) {
            return $this;
        }

        $handle   = $this->prefix . $handle;
        $strategy = \strtolower(\trim($strategy));

        if ('module' === $strategy) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            \wp_script_add_data($handle, 'type', 'module');
        } elseif (\in_array($strategy, ['async', 'defer'], true)) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            \wp_script_add_data($handle, 'strategy', $strategy);
        }

        return $this;
    }

    // =========================================================================
    // DATA & INLINE INJECTION
    // =========================================================================

    /** {@inheritDoc} */
    public function data(string $handle, array $data, string $name = 'wp_data'): self
    {
        if (!$this->shouldExecute()) {
            return $this;
        }

        $json = \wp_json_encode($data);
        if (false === $json) {
            return $this;
        }

        $handle = $this->prefix . $handle;
        // IIFE wrapper prevents global scope pollution
        $code = \sprintf('(function(w){w.%s=%s;})(window);', \esc_js($name), $json);

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \wp_add_inline_script($handle, $code, 'before');
        return $this;
    }

    /** {@inheritDoc} */
    public function nonce(string $handle, string $action, string $var = 'nonce'): self
    {
        if (!$this->shouldExecute()) {
            return $this;
        }

        // Uses Woplucore Noncesable if injected, falls back to native WP
        $token = null !== $this->nonces ? $this->nonces->create($action) : \wp_create_nonce($action);
        return $this->data($handle, [$var => $token], 'nonce');
    }

    /** {@inheritDoc} */
    public function inline(string $handle, string $code, string $position = 'after', int $priority = 10): self
    {
        if (!$this->shouldExecute()) {
            return $this;
        }

        $handle   = $this->prefix . $handle;
        $position = \in_array($position, ['before', 'after'], true) ? $position : 'after';

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \wp_add_inline_script($handle, $code, $position, $priority);
        return $this;
    }

    // =========================================================================
    // DEPENDENCY & I18N CONTROL
    // =========================================================================

    /** {@inheritDoc} */
    public function addDeps(string $handle, array $deps): self
    {
        if (!$this->shouldExecute()) {
            return $this;
        }

        $handle    = $this->prefix . $handle;
        $wpScripts = \wp_scripts();
        $registered = $wpScripts->registered[$handle] ?? null;
        $currentDeps = $registered ? ($registered->deps ?: []) : [];
        $merged    = \array_values(\array_unique(\array_merge($currentDeps, $deps)));

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \wp_script_add_data($handle, 'deps', $merged);
        return $this;
    }

    /** {@inheritDoc} */
    public function setDeps(string $handle, array $deps): self
    {
        if (!$this->shouldExecute()) {
            return $this;
        }

        $handle = $this->prefix . $handle;
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \wp_script_add_data($handle, 'deps', $deps);
        return $this;
    }

    /** {@inheritDoc} */
    public function translations(string $handle, string $domain, string $path = ''): self
    {
        if (!$this->shouldExecute()) {
            return $this;
        }

        $handle = $this->prefix . $handle;
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \wp_set_script_translations($handle, $domain, $path);
        return $this;
    }

    // =========================================================================
    // CONDITIONS & UTILITIES
    // =========================================================================

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