<?php
declare(strict_types=1);

namespace Frescoref\Woplucore\Contracts;

/**
 * Interface Hooksable
 *
 * Complete wrapper around the WordPress hook system public API.
 * All methods map to native core functions with automatic tag prefixing.
 * Direct calls to add_action()/apply_filters() in business logic are STRICTLY FORBIDDEN.
 * Includes base methods, advanced queue management, execution state checks,
 * prefix lifecycle control, and production-grade utility wrappers.
 *
 * @package Frescoref\Woplucore\Contracts
 * @since 1.0.0
 */
interface Hooksable
{
    /**
     * Apply filters to a value with auto-prefixed tag.
     *
     * @param string $tag   The name of the filter hook.
     * @param mixed  $value The value to be filtered.
     * @param mixed  ...$args Additional arguments to pass to the filter callbacks.
     *
     * @return mixed The filtered value.
     * @throws \InvalidArgumentException If tag is empty or invalid after normalization.
     */
    public function applyFilters(string $tag, $value, ...$args);

    /**
     * Execute actions with auto-prefixed tag.
     *
     * @param string $tag The name of the action hook.
     * @param mixed  ...$args Additional arguments to pass to the action callbacks.
     *
     * @return void
     * @throws \InvalidArgumentException If tag is empty or invalid after normalization.
     */
    public function doAction(string $tag, ...$args): void;

    /**
     * Add a filter callback with auto-prefixed tag.
     *
     * @param string   $tag          The name of the filter hook.
     * @param callable $callback     The function to be called.
     * @param int      $priority     Priority of the callback. Lower executes earlier.
     * @param int      $acceptedArgs Number of arguments the callback accepts.
     *
     * @return void
     * @throws \InvalidArgumentException If tag is empty or callback is not callable.
     */
    public function addFilter(string $tag, callable $callback, int $priority = 10, int $acceptedArgs = 1): void;

    /**
     * Add an action callback with auto-prefixed tag.
     *
     * @param string   $tag          The name of the action hook.
     * @param callable $callback     The function to be called.
     * @param int      $priority     Priority of the callback. Lower executes earlier.
     * @param int      $acceptedArgs Number of arguments the callback accepts.
     *
     * @return void
     * @throws \InvalidArgumentException If tag is empty or callback is not callable.
     */
    public function addAction(string $tag, callable $callback, int $priority = 10, int $acceptedArgs = 1): void;

    /**
     * Apply filters using an array of arguments (pass-by-reference safe).
     *
     * @param string $tag  The name of the filter hook.
     * @param array  $args Array of arguments. First element is the value to filter.
     *
     * @return mixed The filtered value.
     * @throws \InvalidArgumentException If tag is empty or args array is empty.
     */
    public function applyFiltersRefArray(string $tag, array $args);

    /**
     * Execute actions using an array of arguments (pass-by-reference safe).
     *
     * @param string $tag  The name of the action hook.
     * @param array  $args Array of arguments to pass to the action callbacks.
     *
     * @return void
     * @throws \InvalidArgumentException If tag is empty.
     */
    public function doActionRefArray(string $tag, array $args): void;

    /**
     * Remove a specific filter callback.
     *
     * @param string   $tag      The name of the filter hook.
     * @param callable $callback The function to be removed.
     * @param int      $priority Priority of the callback (must match registration).
     *
     * @return bool True if the callback was removed, false otherwise.
     */
    public function removeFilter(string $tag, callable $callback, int $priority = 10): bool;

    /**
     * Remove a specific action callback.
     *
     * @param string   $tag      The name of the action hook.
     * @param callable $callback The function to be removed.
     * @param int      $priority Priority of the callback (must match registration).
     *
     * @return bool True if the callback was removed, false otherwise.
     */
    public function removeAction(string $tag, callable $callback, int $priority = 10): bool;

    /**
     * Remove all callbacks attached to a filter with auto-prefixed tag.
     *
     * @param string $tag The name of the filter hook.
     *
     * @return void
     */
    public function removeAllFilters(string $tag): void;

    /**
     * Remove all callbacks attached to an action with auto-prefixed tag.
     *
     * @param string $tag The name of the action hook.
     *
     * @return void
     */
    public function removeAllActions(string $tag): void;

    /**
     * Check if a filter has been registered with auto-prefixed tag.
     *
     * @param string                   $tag      The name of the filter hook.
     * @param callable|string|null $callback Optional specific callback or function name to check.
     *
     * @return int|bool The priority if the filter exists, false otherwise.
     */
    public function hasFilter(string $tag, $callback = null);

    /**
     * Check if an action has been registered with auto-prefixed tag.
     *
     * @param string                   $tag      The name of the action hook.
     * @param callable|string|null $callback Optional specific callback or function name to check.
     *
     * @return int|bool The priority if the action exists, false otherwise.
     */
    public function hasAction(string $tag, $callback = null);

    /**
     * Get the number of times an action has been fired.
     *
     * @param string $tag The name of the action hook.
     * @return int The number of times the action was fired.
     */
    public function didAction(string $tag): int;

    /**
     * Check if WordPress is currently executing a specific filter.
     *
     * @param string $tag The filter tag.
     * @return bool True if the filter is currently being applied.
     */
    public function doingFilter(string $tag): bool;

    /**
     * Check if WordPress is currently executing a specific action.
     *
     * @param string $tag The action tag.
     * @return bool True if the action is currently being fired.
     */
    public function doingAction(string $tag): bool;

    /**
     * Retrieve the name of the filter currently being processed.
     *
     * @return string The current filter tag. Empty string if not in filter context.
     */
    public function currentFilter(): string;

    /**
     * Retrieve the name of the action currently being processed.
     *
     * @return string The current action tag. Empty string if not in action context.
     */
    public function currentAction(): string;

    /**
     * Trigger a deprecated filter notice with migration message.
     *
     * @param string $tag     The name of the deprecated filter.
     * @param mixed  $value   The value to be filtered.
     * @param string $version The version when deprecated.
     * @param string $message Optional message explaining the replacement.
     * @param mixed  ...$args Additional arguments.
     *
     * @return mixed The filtered value.
     */
    public function applyFiltersDeprecated(string $tag, $value, string $version, string $message = '', ...$args);

    /**
     * Trigger a deprecated action notice with migration message.
     *
     * @param string $tag     The name of the deprecated action.
     * @param string $version The version when deprecated.
     * @param string $message Optional message explaining the replacement.
     * @param mixed  ...$args Additional arguments.
     *
     * @return void
     */
    public function doActionDeprecated(string $tag, string $version, string $message = '', ...$args): void;

    /**
     * Register an event subscriber for batch hook binding.
     * Subscriber object must implement `subscribe(Hooksable $hooks): void`.
     *
     * @param object $subscriber The subscriber instance.
     *
     * @return void
     * @throws \InvalidArgumentException If subscriber does not implement the required method.
     */
    public function subscribe(object $subscriber): void;

    /**
     * Add a filter that executes exactly once and auto-removes itself.
     *
     * @param string   $tag          The name of the filter hook.
     * @param callable $callback     The function to be called.
     * @param int      $priority     Priority of the callback.
     * @param int      $acceptedArgs Number of arguments the callback accepts.
     *
     * @return void
     */
    public function addFilterOnce(string $tag, callable $callback, int $priority = 10, int $acceptedArgs = 1): void;

    /**
     * Add an action that executes exactly once and auto-removes itself.
     *
     * @param string   $tag          The name of the action hook.
     * @param callable $callback     The function to be called.
     * @param int      $priority     Priority of the callback.
     * @param int      $acceptedArgs Number of arguments the callback accepts.
     *
     * @return void
     */
    public function addActionOnce(string $tag, callable $callback, int $priority = 10, int $acceptedArgs = 1): void;

    /**
     * Apply filters until the first callback returns a non-null result.
     *
     * @param string $tag   The name of the filter hook.
     * @param mixed  $value The value to be filtered.
     * @param mixed  ...$args Additional arguments.
     *
     * @return mixed The first non-null filtered value, or original $value if none found.
     */
    public function applyUntil(string $tag, $value, ...$args);

    /**
     * Execute actions until the first callback returns a non-null result.
     *
     * @param string $tag  The name of the action hook.
     * @param mixed  ...$args Additional arguments.
     *
     * @return mixed|null The first non-null returned value, or null.
     */
    public function doUntil(string $tag, ...$args);

    /**
     * Remove all callbacks (both filters and actions) for a given tag.
     *
     * @param string $tag The hook tag.
     *
     * @return void
     */
    public function clearCallbacks(string $tag): void;

    /**
     * Check if any callbacks exist for a tag (action or filter).
     *
     * @param string $tag The hook tag.
     *
     * @return bool True if callbacks exist.
     */
    public function hasCallbacks(string $tag): bool;

    /**
     * Check if a specific callback exists for a tag and return its priority.
     *
     * @param string   $tag      The hook tag.
     * @param callable $callback The callback to check.
     *
     * @return int|bool Priority if exists, false otherwise.
     */
    public function hasCallback(string $tag, callable $callback);

    /**
     * Execute a callback with a temporarily overridden prefix.
     *
     * @param string   $prefix   The temporary prefix to use. Empty string disables auto-prefixing.
     * @param callable $callback The function to execute within the scope.
     *
     * @return void
     */
    public function scope(string $prefix, callable $callback): void;

    /**
     * Register multiple hooks from a configuration array.
     *
     * @param array<int, array{type: string, tag: string, callback: callable, priority: int, args: int}> $definitions
     *
     * @return void
     * @throws \InvalidArgumentException If definition structure is invalid.
     */
    public function registerMany(array $definitions): void;

    // =========================================================================
    // PREFIX MANAGEMENT
    // =========================================================================

    /**
     * Get the currently active prefix (top value of the scope stack).
     *
     * @return string The active prefix string.
     */
    public function getPrefix(): string;

    /**
     * Get the original base prefix set during construction.
     * Ignores temporary scope overrides.
     *
     * @return string The base prefix string.
     */
    public function getBasePrefix(): string;

    /**
     * Set a new base prefix. Resets the scope stack to a single level.
     *
     * @param string $prefix The new prefix. Empty string disables auto-prefixing.
     *
     * @return void
     */
    public function setPrefix(string $prefix): void;

    /**
     * Clear the active prefix (sets to empty string).
     * Subsequent calls will treat tags as external/core hooks without prefixing.
     *
     * @return void
     */
    public function clearPrefix(): void;

    /**
     * Check if the current active prefix is non-empty.
     *
     * @return bool True if auto-prefixing is currently active.
     */
    public function hasPrefix(): bool;

    /**
     * Remove the current active prefix from a tag if present.
     * Handles double-prefix edge cases safely. Useful for reverse-mapping or logging.
     *
     * @param string $tag The fully qualified or raw hook tag.
     *
     * @return string The tag with the current prefix stripped.
     */
    public function stripPrefix(string $tag): string;

    /**
     * Reset the prefix stack to the base prefix.
     * Clears all temporary scope overrides without recreating the instance.
     *
     * @return void
     */
    public function resetPrefix(): void;

    /**
     * Check if a given string contains the currently active prefix.
     * Does not normalize or modify the input. Useful for validation, routing, and debugging.
     *
     * @param string $tag The tag to inspect.
     *
     * @return bool True if the tag starts with the active prefix and separator.
     */
    public function isPrefixed(string $tag): bool;

    /**
     * Clear internal tag cache and execution tracking state.
     * Prevents memory leaks in long-running processes. Preserves registered WP callbacks.
     *
     * @return void
     */
    public function clearState(): void;

    /**
     * Resolve and normalize a hook tag with the current prefix without executing it.
     * Useful for manual debugging, logging, or external integrations.
     *
     * @param string $tag The raw hook tag.
     *
     * @return string The prefixed, normalized, and validated tag.
     * @throws \InvalidArgumentException If tag is empty or invalid after normalization.
     */
    public function resolveTag(string $tag): string;

    // =========================================================================
    // ADVANCED UTILITIES
    // =========================================================================

    /**
     * Register a tag alias.
     * When the original $tag is fired, the $alias tag is automatically fired with identical arguments.
     * Useful for API versioning, legacy compatibility, and modular event routing.
     *
     * @param string $tag   The original hook tag.
     * @param string $alias The alias tag to mirror.
     *
     * @return void
     */
    public function alias(string $tag, string $alias): void;

    /**
     * Defer action execution until the `shutdown` phase.
     * Useful for heavy operations (logging, emails, cache warm-up) to avoid HTTP blocking.
     *
     * @param string $tag  The name of the action hook.
     * @param mixed  ...$args Arguments to pass when deferred action fires.
     *
     * @return void
     */
    public function defer(string $tag, ...$args): void;

    /**
     * Remove all callbacks for tags matching a regex pattern.
     *
     * @param string $pattern PCRE regex pattern to match against registered tags.
     *
     * @return void
     */
    public function clearByPattern(string $pattern): void;

    /**
     * Dynamically change the priority of an already registered callback.
     *
     * @param string   $tag         The hook tag.
     * @param callable $callback    The registered callback.
     * @param int      $newPriority New execution priority.
     *
     * @return bool True if successfully moved, false otherwise.
     */
    public function changePriority(string $tag, callable $callback, int $newPriority): bool;

    /**
     * Get registered callbacks for a specific tag with their priorities.
     *
     * @param string $tag The hook tag.
     *
     * @return array<int, array{priority: int, callback: callable}> Structured callback list.
     */
    public function getCallbacks(string $tag): array;

    /**
     * Get execution statistics for the current request.
     *
     * @return array<string, int> Map of tag => fire count.
     */
    public function getStats(): array;
}