<?php
declare(strict_types=1);

namespace Frescoref\Woplucore\Utils;

use Frescoref\Woplucore\HooksManager;

/**
 * Class Hooks
 *
 * @package Frescoref\Woplucore\Utils
 * @since 1.0.0
 */
class Hooks
{
    /**
     * Resolved manager instance.
     *
     * @var HooksManager|null
     */
    private static $instance = null;

    /**
     * Bind prefix for advanced features (auto-prefixing, scoping, caching, tag registry).
     *
     * @param string $prefix Optional prefix for auto-prefixing. Empty string disables it.
     * @return void
     */
    public static function configure(string $prefix = ''): void
    {
        self::$instance = new HooksManager($prefix);
    }

    /**
     * Resolve underlying manager instance. Creates default fallback if not configured.
     *
     * @return HooksManager Resolved hooks manager.
     */
    private static function instance(): HooksManager
    {
        if (null === self::$instance) {
            self::$instance = new HooksManager('');
        }
        return self::$instance;
    }

    /** {@inheritDoc HooksManager::applyFilters()} */
    public static function applyFilters(string $tag, $value, ...$args) { return self::instance()->applyFilters($tag, $value, ...$args); }
    /** {@inheritDoc HooksManager::doAction()} */
    public static function doAction(string $tag, ...$args): void { self::instance()->doAction($tag, ...$args); }
    /** {@inheritDoc HooksManager::addFilter()} */
    public static function addFilter(string $tag, callable $callback, int $priority = 10, int $acceptedArgs = 1): void { self::instance()->addFilter($tag, $callback, $priority, $acceptedArgs); }
    /** {@inheritDoc HooksManager::addAction()} */
    public static function addAction(string $tag, callable $callback, int $priority = 10, int $acceptedArgs = 1): void { self::instance()->addAction($tag, $callback, $priority, $acceptedArgs); }
    /** {@inheritDoc HooksManager::applyFiltersRefArray()} */
    public static function applyFiltersRefArray(string $tag, array $args) { return self::instance()->applyFiltersRefArray($tag, $args); }
    /** {@inheritDoc HooksManager::doActionRefArray()} */
    public static function doActionRefArray(string $tag, array $args): void { self::instance()->doActionRefArray($tag, $args); }
    /** {@inheritDoc HooksManager::removeFilter()} */
    public static function removeFilter(string $tag, callable $callback, int $priority = 10): bool { return self::instance()->removeFilter($tag, $callback, $priority); }
    /** {@inheritDoc HooksManager::removeAction()} */
    public static function removeAction(string $tag, callable $callback, int $priority = 10): bool { return self::instance()->removeAction($tag, $callback, $priority); }
    /** {@inheritDoc HooksManager::removeAllFilters()} */
    public static function removeAllFilters(string $tag): void { self::instance()->removeAllFilters($tag); }
    /** {@inheritDoc HooksManager::removeAllActions()} */
    public static function removeAllActions(string $tag): void { self::instance()->removeAllActions($tag); }
    /** {@inheritDoc HooksManager::hasFilter()} */
    public static function hasFilter(string $tag, $callback = null) { return self::instance()->hasFilter($tag, $callback); }
    /** {@inheritDoc HooksManager::hasAction()} */
    public static function hasAction(string $tag, $callback = null) { return self::instance()->hasAction($tag, $callback); }
    /** {@inheritDoc HooksManager::didAction()} */
    public static function didAction(string $tag): int { return self::instance()->didAction($tag); }
    /** {@inheritDoc HooksManager::doingFilter()} */
    public static function doingFilter(string $tag): bool { return self::instance()->doingFilter($tag); }
    /** {@inheritDoc HooksManager::doingAction()} */
    public static function doingAction(string $tag): bool { return self::instance()->doingAction($tag); }
    /** {@inheritDoc HooksManager::currentFilter()} */
    public static function currentFilter(): string { return self::instance()->currentFilter(); }
    /** {@inheritDoc HooksManager::currentAction()} */
    public static function currentAction(): string { return self::instance()->currentAction(); }
    /** {@inheritDoc HooksManager::applyFiltersDeprecated()} */
    public static function applyFiltersDeprecated(string $tag, $value, string $version, string $message = '', ...$args) { return self::instance()->applyFiltersDeprecated($tag, $value, $version, $message, ...$args); }
    /** {@inheritDoc HooksManager::doActionDeprecated()} */
    public static function doActionDeprecated(string $tag, string $version, string $message = '', ...$args): void { self::instance()->doActionDeprecated($tag, $version, $message, ...$args); }
    /** {@inheritDoc HooksManager::subscribe()} */
    public static function subscribe(object $subscriber): void { self::instance()->subscribe($subscriber); }
    /** {@inheritDoc HooksManager::addFilterOnce()} */
    public static function addFilterOnce(string $tag, callable $callback, int $priority = 10, int $acceptedArgs = 1): void { self::instance()->addFilterOnce($tag, $callback, $priority, $acceptedArgs); }
    /** {@inheritDoc HooksManager::addActionOnce()} */
    public static function addActionOnce(string $tag, callable $callback, int $priority = 10, int $acceptedArgs = 1): void { self::instance()->addActionOnce($tag, $callback, $priority, $acceptedArgs); }
    /** {@inheritDoc HooksManager::applyUntil()} */
    public static function applyUntil(string $tag, $value, ...$args) { return self::instance()->applyUntil($tag, $value, ...$args); }
    /** {@inheritDoc HooksManager::doUntil()} */
    public static function doUntil(string $tag, ...$args) { self::instance()->doUntil($tag, ...$args); }
    /** {@inheritDoc HooksManager::clearCallbacks()} */
    public static function clearCallbacks(string $tag): void { self::instance()->clearCallbacks($tag); }
    /** {@inheritDoc HooksManager::hasCallbacks()} */
    public static function hasCallbacks(string $tag): bool { return self::instance()->hasCallbacks($tag); }
    /** {@inheritDoc HooksManager::hasCallback()} */
    public static function hasCallback(string $tag, callable $callback) { return self::instance()->hasCallback($tag, $callback); }
    /** {@inheritDoc HooksManager::forget()} */
    public static function forget(string $tag): void { self::instance()->forget($tag); }
    /** {@inheritDoc HooksManager::hasListeners()} */
    public static function hasListeners(string $tag): bool { return self::instance()->hasListeners($tag); }
    /** {@inheritDoc HooksManager::hasListener()} */
    public static function hasListener(string $tag, callable $callback) { return self::instance()->hasListener($tag, $callback); }
    /** {@inheritDoc HooksManager::scope()} */
    public static function scope(string $prefix, callable $callback): void { self::instance()->scope($prefix, $callback); }
    /** {@inheritDoc HooksManager::registerMany()} */
    public static function registerMany(array $definitions): void { self::instance()->registerMany($definitions); }
    /** {@inheritDoc HooksManager::getPrefix()} */
    public static function getPrefix(): string { return self::instance()->getPrefix(); }
    /** {@inheritDoc HooksManager::getBasePrefix()} */
    public static function getBasePrefix(): string { return self::instance()->getBasePrefix(); }
    /** {@inheritDoc HooksManager::setPrefix()} */
    public static function setPrefix(string $prefix): void { self::instance()->setPrefix($prefix); }
    /** {@inheritDoc HooksManager::clearPrefix()} */
    public static function clearPrefix(): void { self::instance()->clearPrefix(); }
    /** {@inheritDoc HooksManager::hasPrefix()} */
    public static function hasPrefix(): bool { return self::instance()->hasPrefix(); }
    /** {@inheritDoc HooksManager::stripPrefix()} */
    public static function stripPrefix(string $tag): string { return self::instance()->stripPrefix($tag); }
    /** {@inheritDoc HooksManager::resetPrefix()} */
    public static function resetPrefix(): void { self::instance()->resetPrefix(); }
    /** {@inheritDoc HooksManager::clearState()} */
    public static function clearState(): void { self::instance()->clearState(); }
    /** {@inheritDoc HooksManager::resolveTag()} */
    public static function resolveTag(string $tag): string { return self::instance()->resolveTag($tag); }
    /** {@inheritDoc HooksManager::isPrefixed()} */
    public static function isPrefixed(string $tag): bool { return self::instance()->isPrefixed($tag); }
    /** {@inheritDoc HooksManager::alias()} */
    public static function alias(string $tag, string $alias): void { self::instance()->alias($tag, $alias); }
    /** {@inheritDoc HooksManager::defer()} */
    public static function defer(string $tag, ...$args): void { self::instance()->defer($tag, ...$args); }
    /** {@inheritDoc HooksManager::clearByPattern()} */
    public static function clearByPattern(string $pattern): void { self::instance()->clearByPattern($pattern); }
    /** {@inheritDoc HooksManager::changePriority()} */
    public static function changePriority(string $tag, callable $callback, int $newPriority): bool { return self::instance()->changePriority($tag, $callback, $newPriority); }
    /** {@inheritDoc HooksManager::getCallbacks()} */
    public static function getCallbacks(string $tag): array { return self::instance()->getCallbacks($tag); }
    /** {@inheritDoc HooksManager::getStats()} */
    public static function getStats(): array { return self::instance()->getStats(); }
}