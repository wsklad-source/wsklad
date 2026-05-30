<?php declare(strict_types=1);

namespace Frescoref\Woplucore\Utils;

use Frescoref\Woplucore\NoncesManager;

/**
 * Class Nonces
 *
 * @package Frescoref\Woplucore\Utils
 * @since 1.0.0
 */
class Nonces
{
    /**
     * Resolved manager instance.
     *
     * @var NoncesManager|null
     */
    private static $instance = null;

    /**
     * Bind prefix and optional hooks manager for advanced features.
     *
     * @param string $prefix Optional prefix for action scoping. Empty string disables prefixing.
     * @return void
     */
    public static function configure(string $prefix = ''): void
    {
        self::$instance = new NoncesManager($prefix);
    }

    /**
     * Resolve underlying manager instance. Creates default fallback if not configured.
     *
     * @return NoncesManager Resolved nonces manager.
     */
    private static function instance(): NoncesManager
    {
        if (null === self::$instance)
        {
            self::$instance = new NoncesManager('');
        }
        return self::$instance;
    }

    /** {@inheritDoc NoncesManager::create()} */
    public static function create(string $action): string { return self::instance()->create($action); }
    /** {@inheritDoc NoncesManager::createOnce()} */
    public static function createOnce(string $action, int $ttl = 3600): string { return self::instance()->createOnce($action, $ttl); }
    /** {@inheritDoc NoncesManager::verify()} */
    public static function verify(string $token, string $action, bool $strict = false): bool { return self::instance()->verify($token, $action, $strict); }
    /** {@inheritDoc NoncesManager::verifyOnce()} */
    public static function verifyOnce(string $token, string $action): bool { return self::instance()->verifyOnce($token, $action); }
    /** {@inheritDoc NoncesManager::verifyWithReport()} */
    public static function verifyWithReport(string $token, string $action, bool $strict = false): array { return self::instance()->verifyWithReport($token, $action, $strict); }
    /** {@inheritDoc NoncesManager::isExpired()} */
    public static function isExpired(string $token, string $action): bool { return self::instance()->isExpired($token, $action); }
    /** {@inheritDoc NoncesManager::getTimeRemaining()} */
    public static function getTimeRemaining(string $token, string $action): int { return self::instance()->getTimeRemaining($token, $action); }
    /** {@inheritDoc NoncesManager::getSecurityReport()} */
    public static function getSecurityReport(string $token, string $action): array { return self::instance()->getSecurityReport($token, $action); }
    /** {@inheritDoc NoncesManager::field()} */
    public static function field(string $action, string $name = '_wpnonce'): void { self::instance()->field($action, $name); }
    /** {@inheritDoc NoncesManager::url()} */
    public static function url(string $action, string $url, string $name = '_wpnonce'): string { return self::instance()->url($action, $url, $name); }
    /** {@inheritDoc NoncesManager::attribute()} */
    public static function attribute(string $action, string $name = 'data-nonce'): string { return self::instance()->attribute($action, $name); }
    /** {@inheritDoc NoncesManager::localize()} */
    public static function localize(string $action, string $handle, string $name = 'nonce'): void { self::instance()->localize($action, $handle, $name); }
    /** {@inheritDoc NoncesManager::extractFromRequest()} */
    public static function extractFromRequest(string $name = '_wpnonce'): string { return self::instance()->extractFromRequest($name); }
    /** {@inheritDoc NoncesManager::extractFromUrl()} */
    public static function extractFromUrl(string $url, string $name = '_wpnonce'): string { return self::instance()->extractFromUrl($url, $name); }
    /** {@inheritDoc NoncesManager::checkRequest()} */
    public static function checkRequest(string $action, string $name = '_wpnonce', bool $strict = false): bool { return self::instance()->checkRequest($action, $name, $strict); }
    /** {@inheritDoc NoncesManager::checkReferer()} */
    public static function checkReferer(bool $strict = false): bool { return self::instance()->checkReferer($strict); }
    /** {@inheritDoc NoncesManager::validateOrigin()} */
    public static function validateOrigin(bool $strict = false): bool { return self::instance()->validateOrigin($strict); }
    /** {@inheritDoc NoncesManager::onFailure()} */
    public static function onFailure(callable $handler): void { self::instance()->onFailure($handler); }
    /** {@inheritDoc NoncesManager::setStrictMode()} */
    public static function setStrictMode(bool $strict): void { self::instance()->setStrictMode($strict); }
    /** {@inheritDoc NoncesManager::requireSsl()} */
    public static function requireSsl(bool $require = true): void { self::instance()->requireSsl($require); }
    /** {@inheritDoc NoncesManager::guard()} */
    public static function guard(string $action, bool $strict = false): void { self::instance()->guard($action, $strict); }
    /** {@inheritDoc NoncesManager::guardRedirect()} */
    public static function guardRedirect(string $action, string $fallback, bool $strict = false): void { self::instance()->guardRedirect($action, $fallback, $strict); }
    /** {@inheritDoc NoncesManager::revokeAction()} */
    public static function revokeAction(string $action): void { self::instance()->revokeAction($action); }
    /** {@inheritDoc NoncesManager::rotate()} */
    public static function rotate(string $action, bool $invalidateOld = true): string { return self::instance()->rotate($action, $invalidateOld); }
    /** {@inheritDoc NoncesManager::migrateAction()} */
    public static function migrateAction(string $from, string $to): void { self::instance()->migrateAction($from, $to); }
    /** {@inheritDoc NoncesManager::clearExpired()} */
    public static function clearExpired(?int $beforeTimestamp = null): int { return self::instance()->clearExpired($beforeTimestamp); }
    /** {@inheritDoc NoncesManager::batchCreate()} */
    public static function batchCreate(array $actions): array { return self::instance()->batchCreate($actions); }
    /** {@inheritDoc NoncesManager::getTick()} */
    public static function getTick(): int { return self::instance()->getTick(); }
    /** {@inheritDoc NoncesManager::getPrefix()} */
    public static function getPrefix(): string { return self::instance()->getPrefix(); }
    /** {@inheritDoc NoncesManager::setPrefix()} */
    public static function setPrefix(string $prefix): void { self::instance()->setPrefix($prefix); }
    /** {@inheritDoc NoncesManager::resolveAction()} */
    public static function resolveAction(string $action): string { return self::instance()->resolveAction($action); }
}