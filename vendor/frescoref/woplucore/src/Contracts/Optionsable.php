<?php declare(strict_types=1);

namespace Frescoref\Woplucore\Contracts;

/**
 * Interface Optionsable
 *
 * Lightweight, WP-native wrapper around the Options API.
 * Provides auto-prefixing, explicit autoload control, batch operations,
 * key resolution utilities, and safe default fallbacks.
 * Direct calls to get_option()/update_option()/delete_option() in business logic are STRICTLY FORBIDDEN.
 *
 * @package Frescoref\Woplucore\Contracts
 * @since 1.0.0
 */
interface Optionsable
{
    // =========================================================================
    // CORE CRUD
    // =========================================================================

    /**
     * Retrieve an option value with optional default fallback.
     *
     * @param string $key     Option key (without prefix).
     * @param mixed  $default Fallback value if option does not exist.
     * @return mixed The stored value or default.
     */
    public function get(string $key, $default = null);

    /**
     * Update or create an option.
     *
     * @param string $key      Option key (without prefix).
     * @param mixed  $value    New value to store (arrays/objects auto-serialized by WP).
     * @param bool   $autoload Whether to load option on every page request.
     *                         Keep false unless strictly needed to avoid wp_load_alloptions() bloat.
     * @return bool True if value was updated/created, false on failure or if value unchanged.
     */
    public function set(string $key, $value, bool $autoload = false): bool;

    /**
     * Delete an option from the database.
     *
     * @param string $key Option key (without prefix).
     * @return bool True if option was deleted, false if it didn't exist.
     */
    public function delete(string $key): bool;

    /**
     * Check if an option exists in the database.
     * Bypasses default fallback to detect true existence.
     *
     * @param string $key Option key (without prefix).
     * @return bool True if option exists.
     */
    public function has(string $key): bool;

    // =========================================================================
    // BATCH & STATE
    // =========================================================================

    /**
     * Retrieve multiple options at once.
     *
     * @param array<string>          $keys     List of option keys.
     * @param array<string, mixed>   $defaults Key => default fallback pairs.
     * @return array<string, mixed> Key => value pairs.
     */
    public function getMultiple(array $keys, array $defaults = []): array;

    /**
     * Update multiple options.
     * Stops and returns false on first save failure.
     *
     * @param array<string, mixed> $data Key => value pairs.
     * @return bool True if all options were saved successfully, false otherwise.
     */
    public function setMultiple(array $data): bool;

    /**
     * Check if an option exists and contains a logically empty value.
     * Returns true for null, false, empty string, 0, or empty array.
     *
     * @param string $key Option key.
     * @return bool True if option is logically empty.
     */
    public function isEmpty(string $key): bool;

    /**
     * Toggle a boolean option state.
     *
     * @param string $key Option key.
     * @return bool The new boolean state.
     */
    public function toggle(string $key): bool;

    /**
     * Change the autoload flag for an existing option without changing its value.
     * Useful for performance tuning of legacy options.
     *
     * @param string $key      Option key.
     * @param bool   $autoload Whether to autoload the option on every page request.
     * @return bool True if successfully updated, false if option doesn't exist.
     */
    public function changeAutoload(string $key, bool $autoload = true): bool;

    // =========================================================================
    // PREFIX MANAGEMENT
    // =========================================================================

    /**
     * Get the currently active prefix for option keys.
     *
     * @return string Active prefix string.
     */
    public function getPrefix(): string;

    /**
     * Set a new base prefix for option keys.
     *
     * @param string $prefix New prefix. Empty string disables auto-prefixing.
     * @return void
     */
    public function setPrefix(string $prefix): void;

    /**
     * Check if a given option key starts with the currently active prefix.
     *
     * @param string $key Option key to inspect.
     * @return bool True if the key contains the active prefix.
     */
    public function isPrefixed(string $key): bool;

    /**
     * Remove the active prefix from an option key if present.
     * Useful for reverse-mapping, cleanup routines, or legacy key normalization.
     *
     * @param string $key Fully qualified or raw option key.
     * @return string The key with the current prefix stripped.
     */
    public function stripPrefix(string $key): string;

    /**
     * Resolve and normalize an option key with the current prefix.
     * Ensures consistent formatting for storage, debugging, and external integrations.
     *
     * @param string $key Raw option key.
     * @return string Prefixed, normalized, and validated option key.
     */
    public function resolvePrefix(string $key): string;
}