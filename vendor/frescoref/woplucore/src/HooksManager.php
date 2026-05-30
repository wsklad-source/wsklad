<?php declare(strict_types=1);

namespace Frescoref\Woplucore;

use Frescoref\Woplucore\Contracts\Hooksable;

/**
 * Class HooksManager
 *
 * @package Woplucore
 * @since 1.0.0
 */
class HooksManager implements Hooksable
{
    /**
     * Base prefix set during construction.
     *
     * @var string
     */
    private $basePrefix;

    /**
     * Stack of active prefixes for scoped execution.
     *
     * @var array<int, string>
     */
    private $prefixStack;

    /**
     * Cache for resolved prefixed tags.
     *
     * @var array<string, string>
     */
    private $tagCache = [];

    /**
     * Tracks currently executing tags to prevent infinite recursion.
     *
     * @var array<string, bool>
     */
    private $executing = [];

    /**
     * Registry of all registered tags for pattern matching and debugging.
     *
     * @var array<string, bool>
     */
    private $registeredTags = [];

    /**
     * Internal registry of added callbacks for inspection and priority changes.
     * Structure: [$prefixedTag => [['priority' => int, 'callback' => callable, 'id' => string]]]
     *
     * @var array<string, array<int, array{priority: int, callback: callable, id: string}>>
     */
    private $callbackRegistry = [];

    /**
     * Execution statistics: tag => fire count.
     *
     * @var array<string, int>
     */
    private $executionStats = [];

    /**
     * Tag alias mappings: $tag => $alias.
     *
     * @var array<string, string>
     */
    private $aliases = [];

    /**
     * Constructor.
     *
     * @param string $prefix Optional prefix for auto-prefixing. Empty string disables it.
     */
    public function __construct(string $prefix = '')
    {
        $this->basePrefix = \rtrim($prefix, '_');
        $this->prefixStack = [$this->basePrefix];
    }

    /**
     * Get the current active prefix (top value of the scope stack).
     *
     * @return string The active prefix.
     */
    public function getPrefix(): string
    {
        return \end($this->prefixStack);
    }

    /**
     * Get the original base prefix set during construction.
     *
     * @return string The base prefix.
     */
    public function getBasePrefix(): string
    {
        return $this->basePrefix;
    }

    /**
     * Set a new base prefix. Resets the scope stack to a single level.
     *
     * @param string $prefix The new prefix. Empty string disables auto-prefixing.
     * @return void
     */
    public function setPrefix(string $prefix): void
    {
        $this->basePrefix = \rtrim($prefix, '_');
        $this->prefixStack = [$this->basePrefix];
    }

    /**
     * Clear the active prefix (sets to empty string).
     *
     * @return void
     */
    public function clearPrefix(): void
    {
        $this->prefixStack = [''];
    }

    /**
     * Check if the current active prefix is non-empty.
     *
     * @return bool True if auto-prefixing is active.
     */
    public function hasPrefix(): bool
    {
        return '' !== $this->getPrefix();
    }

    /**
     * Reset the prefix stack to the base prefix.
     *
     * @return void
     */
    public function resetPrefix(): void
    {
        $this->prefixStack = [$this->basePrefix];
    }

    /**
     * Check if a given string contains the currently active prefix.
     *
     * @param string $tag The tag to inspect.
     * @return bool True if the tag starts with the active prefix and separator.
     */
    public function isPrefixed(string $tag): bool
    {
        $prefix = $this->getPrefix();
        return '' !== $prefix && \strpos($tag, $prefix . '_') === 0;
    }

    /**
     * Remove the current active prefix from a tag if present.
     *
     * @param string $tag The fully qualified or raw hook tag.
     * @return string The tag with the current prefix stripped.
     */
    public function stripPrefix(string $tag): string
    {
        $prefix = $this->getPrefix();
        if ('' === $prefix) {
            return $tag;
        }
        $prefixWithSep = $prefix . '_';
        if (\strpos($tag, $prefixWithSep) === 0) {
            return \substr($tag, \strlen($prefixWithSep));
        }
        return $tag;
    }

    /**
     * Build, normalize, and cache the prefixed hook tag.
     *
     * @param string $tag The original hook tag.
     * @return string The prefixed and normalized hook tag.
     * @throws \InvalidArgumentException If tag is empty or invalid after normalization.
     */
    private function buildTag(string $tag): string
    {
        $currentPrefix = $this->getPrefix();
        $cacheKey = $tag . '|' . $currentPrefix;
        if (isset($this->tagCache[$cacheKey])) {
            return $this->tagCache[$cacheKey];
        }

        if ('' === $tag) {
            throw new \InvalidArgumentException('Hook tag cannot be empty.');
        }

        $normalized = \strtolower(\trim($tag));
        $normalized = \preg_replace('/[^a-z0-9_\-\.]/', '_', $normalized);
        $normalized = \preg_replace('/_+/', '_', $normalized);
        $normalized = \trim($normalized, '_-.');

        if ('' === $normalized) {
            throw new \InvalidArgumentException('Hook tag contains only invalid characters after normalization.');
        }

        if ('' === $currentPrefix) {
            $this->tagCache[$cacheKey] = $normalized;
            $this->registeredTags[$normalized] = true;
            return $normalized;
        }

        $prefixWithSep = $currentPrefix . '_';
        if (\strpos($normalized, $prefixWithSep) === 0) {
            $this->tagCache[$cacheKey] = $normalized;
            $this->registeredTags[$normalized] = true;
            return $normalized;
        }

        $final = $prefixWithSep . $normalized;
        $this->tagCache[$cacheKey] = $final;
        $this->registeredTags[$final] = true;
        return $final;
    }

    /**
     * Generate a unique ID for a callable for registry tracking.
     *
     * @param callable $callback The callable to identify.
     * @return string Unique identifier string.
     */
    private function getCallableId(callable $callback): string
    {
        if (\is_array($callback)) {
            return \is_object($callback[0]) ? \spl_object_hash($callback[0]) . '::' . $callback[1] : $callback[0] . '::' . $callback[1];
        }
        if (\is_object($callback)) {
            return \spl_object_hash($callback);
        }
        if (\is_string($callback)) {
            return $callback;
        }
        return \spl_object_hash($callback);
    }

    /**
     * Register a callback in the internal registry.
     *
     * @param string   $tag      The prefixed tag.
     * @param int      $priority Execution priority.
     * @param callable $callback The callable.
     * @return void
     */
    private function registerCallback(string $tag, int $priority, callable $callback): void
    {
        $id = $this->getCallableId($callback);
        if (!isset($this->callbackRegistry[$tag])) {
            $this->callbackRegistry[$tag] = [];
        }
        $this->callbackRegistry[$tag][] = [
            'priority' => $priority,
            'callback' => $callback,
            'id' => $id,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function resolveTag(string $tag): string
    {
        return $this->buildTag($tag);
    }

    /**
     * {@inheritDoc}
     */
    public function scope(string $prefix, callable $callback): void
    {
        $cleanPrefix = \rtrim($prefix, '_');
        $this->prefixStack[] = $cleanPrefix;
        try {
            $callback($this);
        } finally {
            \array_pop($this->prefixStack);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function registerMany(array $definitions): void
    {
        foreach ($definitions as $def) {
            if (!isset($def['type'], $def['tag'], $def['callback'])) {
                throw new \InvalidArgumentException('Hook definition must contain type, tag, and callback keys.');
            }
            $type = \strtolower($def['type']);
            $tag = $def['tag'];
            $callback = $def['callback'];
            $priority = isset($def['priority']) ? (int) $def['priority'] : 10;
            $acceptedArgs = isset($def['args']) ? (int) $def['args'] : 1;

            if ('action' === $type) {
                $this->addAction($tag, $callback, $priority, $acceptedArgs);
            } elseif ('filter' === $type) {
                $this->addFilter($tag, $callback, $priority, $acceptedArgs);
            } else {
                throw new \InvalidArgumentException("Hook type must be 'action' or 'filter'. Given: {$type}");
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function applyFilters(string $tag, $value, ...$args)
    {
        $prefixed = $this->buildTag($tag);
        if (!empty($this->executing[$prefixed])) {
            return $value;
        }

        $this->executing[$prefixed] = true;
        $this->executionStats[$prefixed] = ($this->executionStats[$prefixed] ?? 0) + 1;

        try {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            $result = \apply_filters($prefixed, $value, ...$args);
        } finally {
            unset($this->executing[$prefixed]);
        }

        // Handle aliases
        if (isset($this->aliases[$prefixed])) {
            $aliasTag = $this->aliases[$prefixed];
            $this->doAction($aliasTag, ...$args);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function doAction(string $tag, ...$args): void
    {
        $prefixed = $this->buildTag($tag);
        if (!empty($this->executing[$prefixed])) {
            return;
        }

        $this->executing[$prefixed] = true;
        $this->executionStats[$prefixed] = ($this->executionStats[$prefixed] ?? 0) + 1;

        try {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            \do_action($prefixed, ...$args);
        } finally {
            unset($this->executing[$prefixed]);
        }

        // Handle aliases
        if (isset($this->aliases[$prefixed])) {
            $aliasTag = $this->aliases[$prefixed];
            $this->doAction($aliasTag, ...$args);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function addFilter(string $tag, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        $prefixed = $this->buildTag($tag);
        $this->registerCallback($prefixed, $priority, $callback);
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \add_filter($prefixed, $callback, $priority, $acceptedArgs);
    }

    /**
     * {@inheritDoc}
     */
    public function addAction(string $tag, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        $prefixed = $this->buildTag($tag);
        $this->registerCallback($prefixed, $priority, $callback);
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \add_action($prefixed, $callback, $priority, $acceptedArgs);
    }

    /**
     * {@inheritDoc}
     */
    public function applyFiltersRefArray(string $tag, array $args)
    {
        if (empty($args)) {
            throw new \InvalidArgumentException('Arguments array must not be empty for applyFiltersRefArray.');
        }
        $prefixed = $this->buildTag($tag);
        if (!empty($this->executing[$prefixed])) {
            return $args[0] ?? null;
        }

        $this->executing[$prefixed] = true;
        $this->executionStats[$prefixed] = ($this->executionStats[$prefixed] ?? 0) + 1;

        try {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            $result = \apply_filters_ref_array($prefixed, $args);
        } finally {
            unset($this->executing[$prefixed]);
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function doActionRefArray(string $tag, array $args): void
    {
        $prefixed = $this->buildTag($tag);
        if (!empty($this->executing[$prefixed])) {
            return;
        }
        $this->executing[$prefixed] = true;
        $this->executionStats[$prefixed] = ($this->executionStats[$prefixed] ?? 0) + 1;

        try {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            \do_action_ref_array($prefixed, $args);
        } finally {
            unset($this->executing[$prefixed]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function removeFilter(string $tag, callable $callback, int $priority = 10): bool
    {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        $removed = \remove_filter($this->buildTag($tag), $callback, $priority);
        if ($removed) {
            $this->removeFromRegistry($this->buildTag($tag), $callback, $priority);
        }
        return $removed;
    }

    /**
     * {@inheritDoc}
     */
    public function removeAction(string $tag, callable $callback, int $priority = 10): bool
    {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        $removed = \remove_action($this->buildTag($tag), $callback, $priority);
        if ($removed) {
            $this->removeFromRegistry($this->buildTag($tag), $callback, $priority);
        }
        return $removed;
    }

    /**
     * Remove callback from internal registry.
     *
     * @param string   $tag      Prefixed tag.
     * @param callable $callback The callable.
     * @param int      $priority Execution priority.
     * @return void
     */
    private function removeFromRegistry(string $tag, callable $callback, int $priority): void
    {
        if (!isset($this->callbackRegistry[$tag])) {
            return;
        }
        $id = $this->getCallableId($callback);
        $this->callbackRegistry[$tag] = \array_filter(
            $this->callbackRegistry[$tag],
            static function (array $entry) use ($id, $priority) {
                return $entry['id'] !== $id || $entry['priority'] !== $priority;
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function removeAllFilters(string $tag): void
    {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \remove_all_filters($this->buildTag($tag));
        unset($this->callbackRegistry[$this->buildTag($tag)]);
    }

    /**
     * {@inheritDoc}
     */
    public function removeAllActions(string $tag): void
    {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \remove_all_actions($this->buildTag($tag));
        unset($this->callbackRegistry[$this->buildTag($tag)]);
    }

    /**
     * {@inheritDoc}
     */
    public function hasFilter(string $tag, $callback = null)
    {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        return \has_filter($this->buildTag($tag), $callback);
    }

    /**
     * {@inheritDoc}
     */
    public function hasAction(string $tag, $callback = null)
    {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        return \has_action($this->buildTag($tag), $callback);
    }

    /**
     * {@inheritDoc}
     */
    public function didAction(string $tag): int
    {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        return \did_action($this->buildTag($tag));
    }

    /**
     * {@inheritDoc}
     */
    public function doingFilter(string $tag): bool
    {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        return \doing_filter($this->buildTag($tag));
    }

    /**
     * {@inheritDoc}
     */
    public function doingAction(string $tag): bool
    {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        return \doing_action($this->buildTag($tag));
    }

    /**
     * {@inheritDoc}
     */
    public function currentFilter(): string
    {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        return (string) \current_filter();
    }

    /**
     * {@inheritDoc}
     */
    public function currentAction(): string
    {
        return $this->currentFilter();
    }

    /**
     * {@inheritDoc}
     */
    public function applyFiltersDeprecated(string $tag, $value, string $version, string $message = '', ...$args)
    {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        return \apply_filters_deprecated($this->buildTag($tag), \array_merge([$value], $args), $version, $message);
    }

    /**
     * {@inheritDoc}
     */
    public function doActionDeprecated(string $tag, string $version, string $message = '', ...$args): void
    {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \do_action_deprecated($this->buildTag($tag), $args, $version, $message);
    }

    /**
     * {@inheritDoc}
     */
    public function subscribe(object $subscriber): void
    {
        if (!\method_exists($subscriber, 'subscribe')) {
            throw new \InvalidArgumentException('Subscriber object must implement a public subscribe(Hooksable $hooks): void method.');
        }
        $subscriber->subscribe($this);
    }

    /**
     * {@inheritDoc}
     */
    public function addFilterOnce(string $tag, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        $prefixed = $this->buildTag($tag);
        $wrapper = null;
        // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInClosure
        $wrapper = function () use ($prefixed, $callback, $priority, &$wrapper) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            \remove_filter($prefixed, $wrapper, $priority);
            return $callback(...\func_get_args());
        };
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \add_filter($prefixed, $wrapper, $priority, $acceptedArgs);
        $this->registerCallback($prefixed, $priority, $callback);
    }

    /**
     * {@inheritDoc}
     */
    public function addActionOnce(string $tag, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        $prefixed = $this->buildTag($tag);
        $wrapper = null;
        // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInClosure
        $wrapper = function () use ($prefixed, $callback, $priority, &$wrapper) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            \remove_action($prefixed, $wrapper, $priority);
            $callback(...\func_get_args());
        };
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \add_action($prefixed, $wrapper, $priority, $acceptedArgs);
        $this->registerCallback($prefixed, $priority, $callback);
    }

    /**
     * {@inheritDoc}
     */
    public function applyUntil(string $tag, $value, ...$args)
    {
        return $this->applyFilters($tag, $value, ...$args);
    }

    /**
     * {@inheritDoc}
     */
    public function doUntil(string $tag, ...$args)
    {
        $this->doAction($tag, ...$args);
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function clearCallbacks(string $tag): void
    {
        $prefixed = $this->buildTag($tag);
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \remove_all_actions($prefixed);
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \remove_all_filters($prefixed);
        unset($this->executing[$prefixed], $this->callbackRegistry[$prefixed]);
    }

    /**
     * {@inheritDoc}
     */
    public function hasCallbacks(string $tag): bool
    {
        return $this->hasAction($tag) || $this->hasFilter($tag);
    }

    /**
     * {@inheritDoc}
     */
    public function hasCallback(string $tag, callable $callback)
    {
        $priority = $this->hasAction($tag, $callback);
        if (false !== $priority) {
            return $priority;
        }
        return $this->hasFilter($tag, $callback);
    }

    /**
     * {@inheritDoc}
     */
    public function forget(string $tag): void
    {
        $this->clearCallbacks($tag);
    }

    /**
     * {@inheritDoc}
     */
    public function hasListeners(string $tag): bool
    {
        return $this->hasCallbacks($tag);
    }

    /**
     * {@inheritDoc}
     */
    public function hasListener(string $tag, callable $callback)
    {
        return $this->hasCallback($tag, $callback);
    }

    /**
     * {@inheritDoc}
     */
    public function clearState(): void
    {
        $this->executing = [];
        $this->executionStats = [];
        $this->tagCache = [];
    }

    /**
     * {@inheritDoc}
     */
    public function alias(string $tag, string $alias): void
    {
        $prefixed = $this->buildTag($tag);
        $prefixedAlias = $this->buildTag($alias);
        $this->aliases[$prefixed] = $prefixedAlias;
    }

    /**
     * {@inheritDoc}
     */
    public function defer(string $tag, ...$args): void
    {
        $prefixed = $this->buildTag($tag);
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \add_action('shutdown', static function () use ($prefixed, $args) {
            \do_action($prefixed, ...$args);
        }, 99999);
    }

    /**
     * {@inheritDoc}
     */
    public function clearByPattern(string $pattern): void
    {
        $matchingTags = \preg_grep('/^' . $pattern . '$/', \array_keys($this->registeredTags));
        foreach ($matchingTags as $tag) {
            $this->clearCallbacks($tag);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function changePriority(string $tag, callable $callback, int $newPriority): bool
    {
        $prefixed = $this->buildTag($tag);
        if (!isset($this->callbackRegistry[$prefixed])) {
            return false;
        }

        $id = $this->getCallableId($callback);
        $oldPriority = null;
        foreach ($this->callbackRegistry[$prefixed] as $entry) {
            if ($entry['id'] === $id) {
                $oldPriority = $entry['priority'];
                break;
            }
        }

        if (null === $oldPriority) {
            return false;
        }

        // Remove old
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \remove_action($prefixed, $callback, $oldPriority);
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \remove_filter($prefixed, $callback, $oldPriority);

        // Add new
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \add_action($prefixed, $callback, $newPriority, 1);
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        \add_filter($prefixed, $callback, $newPriority, 1);

        // Update registry
        foreach ($this->callbackRegistry[$prefixed] as &$entry) {
            if ($entry['id'] === $id) {
                $entry['priority'] = $newPriority;
            }
        }
        unset($entry);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getCallbacks(string $tag): array
    {
        $prefixed = $this->buildTag($tag);
        if (!isset($this->callbackRegistry[$prefixed])) {
            return [];
        }
        return \array_values($this->callbackRegistry[$prefixed]);
    }

    /**
     * {@inheritDoc}
     */
    public function getStats(): array
    {
        return $this->executionStats;
    }
}