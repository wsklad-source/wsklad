<?php declare(strict_types=1);

namespace Frescoref\Woplucore;

use Frescoref\Woplucore\Contracts\Hooksable;
use Frescoref\Woplucore\Contracts\Optionsable;

/**
 * Class OptionsManager
 *
 * @package Woplucore
 * @since 1.0.0
 */
class OptionsManager implements Optionsable
{
    /**
     * Active prefix for option keys.
     *
     * @var string
     */
    private $prefix;

    /**
     * Optional hooks manager for audit events.
     *
     * @var Hooksable|null
     */
    private $hooks;

    /**
     * Local cache for resolved option keys to avoid redundant normalization.
     *
     * @var array<string, string>
     */
    private $keyCache = [];

    /**
     * Sentinel value to distinguish between "option does not exist" and "option value is false/null/0".
     * Null bytes prevent accidental storage via WP UI or standard serialization.
     *
     * @var string
     */
    private const SENTINEL = "\0__NOT_FOUND__\0";

    /**
     * Constructor.
     *
     * @param string       $prefix Optional prefix for key scoping. Empty string disables prefixing.
     * @param Hooksable|null $hooks Optional hooks manager for audit logging.
     */
    public function __construct(string $prefix = '', ?Hooksable $hooks = null)
    {
        $this->prefix = \rtrim($prefix, '_');
        $this->hooks  = $hooks;
    }

    // =========================================================================
    // CORE CRUD
    // =========================================================================

    /** {@inheritDoc} */
    public function get(string $key, $default = null)
    {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        return \get_option($this->resolveKey($key), $default);
    }

    /** {@inheritDoc} */
    public function set(string $key, $value, bool $autoload = false): bool
    {
        $resolved = $this->resolveKey($key);
        $exists = self::SENTINEL !== \get_option($resolved, self::SENTINEL);

        $result = $exists
            ? \update_option($resolved, $value)
            : \add_option($resolved, $value, '', $autoload ? 'yes' : 'no');

        if ($result && null !== $this->hooks) {
            $this->hooks->doAction('option_set', $key, $value, $autoload);
        }

        return $result;
    }

    /** {@inheritDoc} */
    public function delete(string $key): bool
    {
        $resolved = $this->resolveKey($key);
        $result   = \delete_option($resolved);

        if ($result) {
            \wp_cache_delete('alloptions', 'options');
            if (null !== $this->hooks) {
                $this->hooks->doAction('option_deleted', $key);
            }
        }

        return $result;
    }

    /** {@inheritDoc} */
    public function has(string $key): bool
    {
        return self::SENTINEL !== \get_option($this->resolveKey($key), self::SENTINEL);
    }

    // =========================================================================
    // BATCH & STATE
    // =========================================================================

    /** {@inheritDoc} */
    public function getMultiple(array $keys, array $defaults = []): array
    {
        $result = [];
        foreach ($keys as $k) {
            $fallback = \array_key_exists($k, $defaults) ? $defaults[$k] : null;
            $result[$k] = $this->get($k, $fallback);
        }
        return $result;
    }

    /** {@inheritDoc} */
    public function setMultiple(array $data): bool
    {
        foreach ($data as $k => $v) {
            if (!$this->set($k, $v)) {
                return false;
            }
        }
        return true;
    }

    /** {@inheritDoc} */
    public function isEmpty(string $key): bool
    {
        $value = $this->get($key, null);
        return \in_array($value, [null, false, '', 0, [], '0'], true);
    }

    /** {@inheritDoc} */
    public function toggle(string $key): bool
    {
        $current  = $this->get($key, false);
        $newState = !$current;
        $this->set($key, $newState);
        return $newState;
    }

    /** {@inheritDoc} */
    public function changeAutoload(string $key, bool $autoload = true): bool
    {
        $resolved = $this->resolveKey($key);
        $current  = \get_option($resolved, self::SENTINEL);

        if (self::SENTINEL === $current) {
            return false;
        }

        $deleted = \delete_option($resolved);
        if (!$deleted) {
            return false;
        }

        $added = \add_option($resolved, $current, '', $autoload ? 'yes' : 'no');
        \wp_cache_delete('alloptions', 'options');

        return $added;
    }

    // =========================================================================
    // PREFIX MANAGEMENT
    // =========================================================================

    /** {@inheritDoc} */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /** {@inheritDoc} */
    public function setPrefix(string $prefix): void
    {
        $this->prefix   = \rtrim($prefix, '_');
        $this->keyCache = [];
    }

    /** {@inheritDoc} */
    public function isPrefixed(string $key): bool
    {
        return '' !== $this->prefix && 0 === \strpos($key, $this->prefix . '_');
    }

    /** {@inheritDoc} */
    public function stripPrefix(string $key): string
    {
        $prefix = $this->prefix;
        if ('' === $prefix)
        {
            return $key;
        }

        $prefixWithSep = $prefix . '_';
        if (0 === \strpos($key, $prefixWithSep)) {
            return \substr($key, \strlen($prefixWithSep));
        }

        return $key;
    }

    /** {@inheritDoc} */
    public function resolvePrefix(string $key): string
    {
        return $this->resolveKey($key);
    }

    // =========================================================================
    // INTERNAL HELPERS
    // =========================================================================

    /**
     * Build, normalize, cache, and sanitize an option key with the current prefix.
     *
     * @param string $key Raw option key.
     * @return string Prefixed and sanitized key safe for wp_options.
     * @throws \InvalidArgumentException If key is empty or invalid after normalization.
     */
    private function resolveKey(string $key): string
    {
        $cacheKey = $this->prefix . '|' . $key;
        if (isset($this->keyCache[$cacheKey])) {
            return $this->keyCache[$cacheKey];
        }

        if ('' === $key) {
            throw new \InvalidArgumentException('Option key cannot be empty.');
        }

        $normalized = \preg_replace(
            ['/[^a-z0-9_.\-]/', '/_+/', '/^[_\-\.]+|[_\-\.]+$/'],
            '_',
            \strtolower(\trim($key))
        );

        if (null === $normalized || '' === $normalized) {
            throw new \InvalidArgumentException('Option key contains only invalid characters after normalization.');
        }

        $final = '' !== $this->prefix ? $this->prefix . '_' . $normalized : $normalized;
        return $this->keyCache[$cacheKey] = \sanitize_key($final);
    }
}