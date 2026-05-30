<?php declare(strict_types=1);

namespace Frescoref\Woplucore;

use Frescoref\Woplucore\Contracts\Hooksable;
use Frescoref\Woplucore\Contracts\Noncesable;

/**
 * Class NoncesManager
 *
 * @package Woplucore
 * @since 1.0.0
 */
class NoncesManager implements Noncesable
{
    /**
     * Active prefix for action and internal key scoping.
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
     * Custom failure handler for guards.
     *
     * @var callable|null
     */
    private $failureHandler;

    /**
     * Enable strict security mode (nonce + referer + session + 12h window).
     *
     * @var bool
     */
    private $strictMode = false;

    /**
     * Enforce HTTPS requirement for verification.
     *
     * @var bool
     */
    private $requireSsl = false;

    /**
     * Registry of transient keys created by this instance for safe cleanup.
     *
     * @var array<string, int> [key => created_timestamp]
     */
    private $transientRegistry = [];

    /**
     * Constructor.
     *
     * @param string       $prefix Optional prefix for action scoping. Empty string disables prefixing.
     * @param Hooksable|null $hooks Optional hooks manager for audit logging.
     */
    public function __construct(string $prefix = '', ?Hooksable $hooks = null)
    {
        $this->prefix = \rtrim($prefix, '_');
        $this->hooks  = $hooks;
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
        $this->prefix = \rtrim($prefix, '_');
    }

    /** {@inheritDoc} */
    public function resolveAction(string $action): string
    {
        if ('' === $action) {
            throw new \InvalidArgumentException('Action identifier cannot be empty.');
        }

        $normalized = \strtolower(\trim($action));
        $normalized = \preg_replace('/[^a-z0-9_\-\.]/', '_', $normalized);
        $normalized = \preg_replace('/_+/', '_', $normalized);
        $normalized = \trim($normalized, '_-.');

        if ('' === $normalized) {
            throw new \InvalidArgumentException('Action contains only invalid characters after normalization.');
        }

        return '' !== $this->prefix ? \sanitize_key($this->prefix . '_' . $normalized) : \sanitize_key($normalized);
    }

    /**
     * Get sanitized internal prefix for transient keys and JS namespaces.
     *
     * @return string Active prefix or 'global' fallback.
     */
    private function getInternalPrefix(): string
    {
        return '' !== $this->prefix ? $this->prefix : 'global';
    }

    // =========================================================================
    // CORE GENERATION
    // =========================================================================

    /** {@inheritDoc} */
    public function create(string $action): string
    {
        return \wp_create_nonce($this->resolveAction($action));
    }

    /** {@inheritDoc} */
    public function createOnce(string $action, int $ttl = 3600): string
    {
        $key   = $this->getTransientKey($action);
        $token = \wp_generate_password(32, false);

        \set_transient($key, $token, $ttl);
        $this->transientRegistry[$key] = \time();
        $this->emit('nonce_once_created', $action, $ttl, $key);

        return $token;
    }

    // =========================================================================
    // VERIFICATION & ANALYSIS
    // =========================================================================

    /** {@inheritDoc} */
    public function verify(string $token, string $action, bool $strict = false): bool
    {
        if ($this->requireSsl && !\is_ssl()) {
            return false;
        }

        $resolved = $this->resolveAction($action);
        if ($this->isRevoked($resolved)) {
            return false;
        }

        $result = \wp_verify_nonce(\sanitize_text_field(\wp_unslash($token)), $resolved);
        $isValid = false !== $result && (!$strict || 1 === $result);

        if ($isValid) {
            $this->emit('nonce_verified', $action);
        } else {
            $this->emit('nonce_failed', $action);
        }

        return $isValid;
    }

    /** {@inheritDoc} */
    public function verifyOnce(string $token, string $action): bool
    {
        if ($this->requireSsl && !\is_ssl()) {
            return false;
        }

        $key    = $this->getTransientKey($action);
        $stored = \get_transient($key);

        if (false === $stored || $stored !== \sanitize_text_field(\wp_unslash($token))) {
            return false;
        }

        \delete_transient($key);
        unset($this->transientRegistry[$key]);
        $this->emit('nonce_once_consumed', $action, $key);
        return true;
    }

    /** {@inheritDoc} */
    public function verifyWithReport(string $token, string $action, bool $strict = false): array
    {
        $resolved = $this->resolveAction($action);
        $result   = \wp_verify_nonce(\sanitize_text_field(\wp_unslash($token)), $resolved);

        $isValid = false !== $result && (!$strict || 1 === $result);
        $expired = false === $result || ($strict && 2 === $result);
        $mismatch = false === $result;

        $remaining = $this->calculateTimeRemaining($result);

        return [
            'valid'             => $isValid,
            'expired'           => $expired,
            'mismatch'          => $mismatch,
            'remaining_seconds' => $remaining,
            'action'            => $resolved,
        ];
    }

    /** {@inheritDoc} */
    public function isExpired(string $token, string $action): bool
    {
        return false === \wp_verify_nonce(\sanitize_text_field(\wp_unslash($token)), $this->resolveAction($action));
    }

    /** {@inheritDoc} */
    public function getTimeRemaining(string $token, string $action): int
    {
        $result = \wp_verify_nonce(\sanitize_text_field(\wp_unslash($token)), $this->resolveAction($action));
        return $this->calculateTimeRemaining($result);
    }

    /** {@inheritDoc} */
    public function getSecurityReport(string $token, string $action): array
    {
        $resolved = $this->resolveAction($action);
        $result   = \wp_verify_nonce(\sanitize_text_field(\wp_unslash($token)), $resolved);
        $isValid  = false !== $result;

        return [
            'valid'           => $isValid,
            'expired'         => 2 === $result,
            'ssl_ok'          => !$this->requireSsl || \is_ssl(),
            'origin_ok'       => $this->validateOrigin(false),
            'referer_ok'      => $this->checkReferer(false),
            'user_logged_in'  => \is_user_logged_in(),
            'tick'            => $this->getTick(),
            'message'         => $isValid ? 'Token verified successfully.' : 'Verification failed.',
        ];
    }

    // =========================================================================
    // OUTPUT & FRONTEND INTEGRATION
    // =========================================================================

    /** {@inheritDoc} */
    public function field(string $action, string $name = '_wpnonce'): void
    {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo \wp_nonce_field($this->resolveAction($action), \sanitize_key($name), true, false);
    }

    /** {@inheritDoc} */
    public function url(string $action, string $url, string $name = '_wpnonce'): string
    {
        return \wp_nonce_url(\esc_url_raw($url), $this->resolveAction($action), \sanitize_key($name));
    }

    /** {@inheritDoc} */
    public function attribute(string $action, string $name = 'data-nonce'): string
    {
        $token = $this->create($action);
        return \sanitize_key($name) . '="' . \esc_attr($token) . '"';
    }

    /** {@inheritDoc} */
    public function localize(string $action, string $handle, string $name = 'nonce'): void
    {
        if (!\wp_script_is($handle, 'registered') && !\wp_script_is($handle, 'enqueued')) {
            return;
        }
        $token = $this->create($action);
        $jsObj = \sanitize_key($this->getInternalPrefix()) . '_nonce';
        \wp_localize_script(\sanitize_key($handle), $jsObj, [\sanitize_key($name) => $token]);
    }

    // =========================================================================
    // REQUEST EXTRACTION & CHECKING
    // =========================================================================

    /** {@inheritDoc} */
    public function extractFromRequest(string $name = '_wpnonce'): string
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if (isset($_REQUEST[$name])) {
            return \sanitize_text_field(\wp_unslash($_REQUEST[$name]));
        }

        $json = $this->extractJsonBody($name);
        if ('' !== $json) {
            return $json;
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if (isset($_SERVER['HTTP_X_WP_NONCE'])) {
            return \sanitize_text_field(\wp_unslash($_SERVER['HTTP_X_WP_NONCE']));
        }

        return '';
    }

    /** {@inheritDoc} */
    public function extractFromUrl(string $url, string $name = '_wpnonce'): string
    {
        $query = \wp_parse_url(\esc_url_raw($url), \PHP_URL_QUERY);
        if (null === $query) {
            return '';
        }
        $args = \wp_parse_args($query);
        return isset($args[$name]) ? \sanitize_text_field(\wp_unslash($args[$name])) : '';
    }

    /** {@inheritDoc} */
    public function checkRequest(string $action, string $name = '_wpnonce', bool $strict = false): bool
    {
        $token = $this->extractFromRequest($name);
        return '' !== $token && $this->verify($token, $action, $strict);
    }

    /** {@inheritDoc} */
    public function checkReferer(bool $strict = false): bool
    {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $referer = isset($_SERVER['HTTP_REFERER']) ? \wp_unslash($_SERVER['HTTP_REFERER']) : '';
        if ('' === $referer) {
            return !$strict;
        }

        $allowed = \admin_url();
        if ($strict) {
            return \strpos(\esc_url_raw($referer), \esc_url_raw($allowed)) === 0;
        }

        return \strpos(\esc_url_raw($referer), \home_url()) === 0 || \strpos(\esc_url_raw($referer), \admin_url()) === 0;
    }

    /** {@inheritDoc} */
    public function validateOrigin(bool $strict = false): bool
    {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? \wp_unslash($_SERVER['HTTP_ORIGIN']) : '';
        if ('' === $origin) {
            return !$strict;
        }

        $home = \trailingslashit(\home_url());
        return \strpos(\esc_url_raw($origin), \esc_url_raw($home)) === 0;
    }

    // =========================================================================
    // GUARDS & FLOW CONTROL
    // =========================================================================

    /** {@inheritDoc} */
    public function onFailure(callable $handler): self
    {
        $this->failureHandler = $handler;
        return $this;
    }

    /** {@inheritDoc} */
    public function setStrictMode(bool $strict): self
    {
        $this->strictMode = $strict;
        return $this;
    }

    /** {@inheritDoc} */
    public function requireSsl(bool $require = true): self
    {
        $this->requireSsl = $require;
        return $this;
    }

    /** {@inheritDoc} */
    public function guard(string $action, bool $strict = false): void
    {
        if ($this->requireSsl && !\is_ssl()) {
            $this->handleFailure($action, 'SSL required for nonce verification.');
        }
        if ($this->strictMode && !$this->checkReferer(true)) {
            $this->handleFailure($action, 'Invalid HTTP Referer in strict mode.');
        }
        if (!$this->checkRequest($action, '_wpnonce', $strict)) {
            $this->handleFailure($action, 'Invalid or missing security token.');
        }
    }

    /** {@inheritDoc} */
    public function guardRedirect(string $action, string $fallback, bool $strict = false): void
    {
        try {
            $this->guard($action, $strict);
        } catch (\RuntimeException $e) {
            \wp_safe_redirect(\esc_url_raw($fallback));
            exit;
        }
    }

    // =========================================================================
    // ADVANCED LIFECYCLE & MAINTENANCE
    // =========================================================================

    /** {@inheritDoc} */
    public function revokeAction(string $action): void
    {
        $resolved = $this->resolveAction($action);
        $revKey   = $this->getInternalKey('revoked', $resolved);
        \set_transient($revKey, \time(), 86400); // 24h revocation window
        $this->cleanupTransients($resolved);
        $this->emit('nonce_revoked', $action);
    }

    /** {@inheritDoc} */
    public function rotate(string $action, bool $invalidateOld = true): string
    {
        if ($invalidateOld) {
            $this->revokeAction($action);
        }
        return $this->create($action);
    }

    /** {@inheritDoc} */
    public function migrateAction(string $from, string $to): void
    {
        $this->revokeAction($from);
        if (null !== $this->hooks) {
            $this->hooks->alias($this->resolveAction($from), $this->resolveAction($to));
        }
        $this->emit('nonce_migrated', $from, $to);
    }

    /** {@inheritDoc} */
    public function clearExpired(?int $beforeTimestamp = null): int
    {
        $cutoff = $beforeTimestamp ?? \time();
        $count  = 0;

        foreach ($this->transientRegistry as $key => $created) {
            if ($created < $cutoff) {
                \delete_transient($key);
                unset($this->transientRegistry[$key]);
                ++$count;
            }
        }

        return $count;
    }

    // =========================================================================
    // BATCH & CONTEXT UTILITIES
    // =========================================================================

    /** {@inheritDoc} */
    public function batchCreate(array $actions): array
    {
        $result = [];
        foreach ($actions as $action) {
            $result[$action] = $this->create($action);
        }
        return $result;
    }

    /** {@inheritDoc} */
    public function getTick(): int
    {
        return (int) \ceil(\time() / 43200);
    }

    // =========================================================================
    // INTERNAL HELPERS
    // =========================================================================

    /**
     * Get sanitized transient key for single-use tokens.
     *
     * @param string $action Base action.
     * @return string Transient key.
     */
    private function getTransientKey(string $action): string
    {
        return $this->getInternalKey('once', $action);
    }

    /**
     * Build a consistent internal key for transients.
     *
     * @param string $type   Key type (e.g., 'once', 'revoked').
     * @param string $action Resolved action string.
     * @return string Sanitized key.
     */
    private function getInternalKey(string $type, string $action): string
    {
        return \sanitize_key($this->getInternalPrefix() . '_nonce_' . $type . '_' . $action);
    }

    /**
     * Calculate remaining seconds until next WP tick change.
     *
     * @param int|false $verifyResult Result from wp_verify_nonce.
     * @return int Seconds remaining.
     */
    private function calculateTimeRemaining($verifyResult): int
    {
        if (false === $verifyResult || 2 === $verifyResult) {
            return 0;
        }
        return 43200 - (\time() % 43200);
    }

    /**
     * Check if action is currently revoked.
     *
     * @param string $resolvedAction Resolved action string.
     * @return bool True if revoked.
     */
    private function isRevoked(string $resolvedAction): bool
    {
        $key = $this->getInternalKey('revoked', $resolvedAction);
        return false !== \get_transient($key);
    }

    /**
     * Clean up transients associated with an action.
     *
     * @param string $resolvedAction Resolved action.
     * @return void
     */
    private function cleanupTransients(string $resolvedAction): void
    {
        foreach (\array_keys($this->transientRegistry) as $key) {
            if (\strpos($key, $resolvedAction) !== false) {
                \delete_transient($key);
                unset($this->transientRegistry[$key]);
            }
        }
    }

    /**
     * Extract nonce from JSON request body safely.
     *
     * @param string $name Parameter name.
     * @return string Token or empty string.
     */
    private function extractJsonBody(string $name): string
    {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $contentType = \wp_unslash($_SERVER['CONTENT_TYPE'] ?? '');
        if (false === \strpos($contentType, 'application/json')) {
            return '';
        }

        $body = \file_get_contents('php://input');
        if (false === $body) {
            return '';
        }

        $data = \json_decode($body, true);
        if (!\is_array($data) || !isset($data[$name])) {
            return '';
        }

        return \sanitize_text_field(\wp_unslash($data[$name]));
    }

    /**
     * Emit audit event if hooks manager is available.
     *
     * @param string $tag Event tag.
     * @param mixed  ...$args Event arguments.
     * @return void
     */
    private function emit(string $tag, ...$args): void
    {
        if (null !== $this->hooks) {
            $this->hooks->doAction($tag, ...$args);
        }
    }

    /**
     * Handle verification failure via custom handler or exception.
     *
     * @param string $action Action identifier.
     * @param string $reason Failure reason.
     * @return void
     * @throws \RuntimeException If no custom handler is set.
     */
    private function handleFailure(string $action, string $reason): void
    {
        if (null !== $this->failureHandler) {
            $report = $this->getSecurityReport($this->extractFromRequest(), $action);
            $report['message'] = $reason;
            ($this->failureHandler)($action, $report);
            return;
        }
        throw new \RuntimeException('Nonce verification failed: ' . \esc_html($reason));
    }
}