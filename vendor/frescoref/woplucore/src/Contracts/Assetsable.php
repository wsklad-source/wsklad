<?php declare(strict_types=1);

namespace Frescoref\Woplucore\Contracts;

/**
 * Interface Assetsable
 *
 * High-level asset orchestrator.
 * Provides unified access to Scriptable and Styleable builders,
 * handle prefixing, global conditions, and state inspection.
 * Direct wp_enqueue_* calls in business logic are STRICTLY FORBIDDEN.
 *
 * @package Frescoref\Woplucore\Contracts
 * @since 1.0.0
 */
interface Assetsable
{
    /**
     * Get script builder for fluent chaining.
     *
     * @return Scriptable
     */
    public function script(): Scriptable;

    /**
     * Get style builder for fluent chaining.
     *
     * @return Styleable
     */
    public function style(): Styleable;

    /**
     * Set a global prefix for all subsequently registered asset handles.
     * Prevents namespace collisions with themes, plugins, and WP core.
     * Resets internal builders to apply new prefix immediately.
     *
     * @param string $prefix Handle prefix (e.g., 'my-plugin-').
     * @return self
     */
    public function prefix(string $prefix): self;

    /**
     * Set a global condition for ALL subsequent asset operations.
     * Automatically chained to every builder returned by script()/style().
     *
     * @param callable $condition Function(): bool
     * @return self
     */
    public function when(callable $condition): self;

    /**
     * Set a global inverse condition for ALL subsequent asset operations.
     * Equivalent to when(fn() => !$condition()).
     *
     * @param callable $condition Function(): bool
     * @return self
     */
    public function unless(callable $condition): self;

    /**
     * Check if a script/style is registered in WP core registry.
     * Auto-applies current prefix to handle before check.
     *
     * @param string $handle Asset handle.
     * @return bool
     */
    public function isRegistered(string $handle): bool;

    /**
     * Check if a script/style is enqueued for current request.
     * Auto-applies current prefix to handle before check.
     *
     * @param string $handle Asset handle.
     * @return bool
     */
    public function isEnqueued(string $handle): bool;

    /**
     * Flush all cached builders, clear global condition stack, and reset state.
     * Safe for CLI workers, long-running processes, or unit testing.
     *
     * @return void
     */
    public function flush(): void;
}