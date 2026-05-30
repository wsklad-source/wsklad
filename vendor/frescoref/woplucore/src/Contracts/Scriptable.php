<?php declare(strict_types=1);

namespace Frescoref\Woplucore\Contracts;

/**
 * Interface Scriptable
 *
 * Lean JS asset manager. Solves: safe PHP→JS data passing, execution control,
 * dependency conflict resolution, conditional loading, and queue debugging.
 * Direct wp_enqueue_script/wp_add_inline_script calls are STRICTLY FORBIDDEN.
 *
 * @package Frescoref\Woplucore\Contracts
 * @since 1.0.0
 */
interface Scriptable
{
    /**
     * Register a script for later enqueueing.
     * Auto-resolves version via filemtime() if $version is null and path is local.
     *
     * @param string        $handle    Unique handle.
     * @param string        $src       URL or relative path.
     * @param array<string> $deps      Dependency handles.
     * @param string|null   $version   Auto-detected if null.
     * @param bool          $inFooter  Load in footer.
     * @return self
     */
    public function register(string $handle, string $src, array $deps = [], ?string $version = null, bool $inFooter = true): self;

    /**
     * Mark registered script for output in the current request.
     *
     * @param string $handle Script handle.
     * @return self
     */
    public function enqueue(string $handle): self;

    /**
     * Dequeue and deregister script in one call.
     * Replaces manual wp_dequeue_script() + wp_deregister_script().
     *
     * @param string $handle Script handle.
     * @return self
     */
    public function remove(string $handle): self;

    /**
     * Add async attribute via wp_script_add_data(). Requires WP 5.2+.
     *
     * @param string $handle Script handle.
     * @return self
     */
    public function async(string $handle): self;

    /**
     * Add defer attribute via wp_script_add_data(). Requires WP 5.2+.
     *
     * @param string $handle Script handle.
     * @return self
     */
    public function defer(string $handle): self;

    /**
     * Set execution strategy (async/defer/module) via wp_script_add_data(). Requires WP 6.3+.
     *
     * @param string $handle   Script handle.
     * @param string $strategy 'async', 'defer', or 'module'.
     * @return self
     */
    public function strategy(string $handle, string $strategy): self;

    /**
     * Safely pass PHP data to JS. Handles wp_json_encode(), escapes for JS context,
     * and wraps in IIFE to prevent global namespace pollution.
     * Replaces manual json_encode() + wp_add_inline_script() boilerplate.
     *
     * @param string $handle Script handle.
     * @param array  $data   Associative data array.
     * @param string $name   JS namespace (default: 'wp_data').
     * @return self
     */
    public function data(string $handle, array $data, string $name = 'wp_data'): self;

    /**
     * Inject CSRF nonce directly into script data for AJAX/REST calls.
     *
     * @param string $handle Script handle.
     * @param string $action Nonce action identifier.
     * @param string $var    JS variable name (default: 'nonce').
     * @return self
     */
    public function nonce(string $handle, string $action, string $var = 'nonce'): self;

    /**
     * Attach raw JS code via wp_add_inline_script().
     * Supports position control and priority (WP 5.0+).
     *
     * @param string $handle   Target handle.
     * @param string $code     Raw JS code.
     * @param string $position 'before' or 'after'.
     * @param int    $priority Execution priority.
     * @return self
     */
    public function inline(string $handle, string $code, string $position = 'after', int $priority = 10): self;

    /**
     * Merge additional dependencies into existing registered script.
     * Does NOT overwrite core/theme deps. Uses wp_script_add_data('deps', ...).
     *
     * @param string        $handle Script handle.
     * @param array<string> $deps   Additional dependency handles.
     * @return self
     */
    public function addDeps(string $handle, array $deps): self;

    /**
     * Fully override dependencies for a registered script.
     * Use with caution: may break theme/plugin compatibility.
     *
     * @param string        $handle Script handle.
     * @param array<string> $deps   New dependency list.
     * @return self
     */
    public function setDeps(string $handle, array $deps): self;

    /**
     * Register translation domain for a script via wp_set_script_translations().
     *
     * @param string $handle Script handle.
     * @param string $domain Text domain.
     * @param string $path   Absolute path to .mo files.
     * @return self
     */
    public function translations(string $handle, string $domain, string $path = ''): self;

    /**
     * Set a loading condition for the next chained operation.
     * Skips registration/enqueue if callback returns false.
     *
     * @param callable $condition Function(): bool
     * @return self
     */
    public function when(callable $condition): self;

    /**
     * Set an inverse loading condition for the next chained operation.
     * Equivalent to when(fn() => !$condition()).
     *
     * @param callable $condition Function(): bool
     * @return self
     */
    public function unless(callable $condition): self;

    /**
     * Resolve version from file modification time.
     * Returns null if file not found, path is external, or inaccessible.
     *
     * @param string $src Absolute file path or URL.
     * @return string|null
     */
    public function autoVersion(string $src): ?string;
}