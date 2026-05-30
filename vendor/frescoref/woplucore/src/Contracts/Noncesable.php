<?php declare(strict_types=1);

namespace Frescoref\Woplucore\Contracts;

/**
 * Interface Noncesable
 *
 * Complete contract for CSRF token generation, verification, and request validation.
 * Provides strict time-window checks, single-use token support, advanced reporting,
 * referer/origin validation, lifecycle rotation, frontend integration helpers,
 * fail-safe guards, and minimal prefix resolution tailored for nonce architecture.
 * Fully compliant with WordPress native nonce lifecycle and PHP 7.4+ strict typing.
 *
 * @package Frescoref\Woplucore\Contracts
 * @since 1.0.0
 */
interface Noncesable
{
    // =========================================================================
    // CORE GENERATION
    // =========================================================================

    /**
     * Create a standard CSRF nonce token.
     *
     * @param string $action Base action identifier.
     * @return string Generated token string.
     */
    public function create(string $action): string;

    /**
     * Create a single-use token stored in transients.
     * Automatically expires and self-destructs on first verification.
     *
     * @param string $action Base action identifier.
     * @param int    $ttl    Token lifetime in seconds.
     * @return string Unique random token string.
     */
    public function createOnce(string $action, int $ttl = 3600): string;

    // =========================================================================
    // VERIFICATION & ANALYSIS
    // =========================================================================

    /**
     * Verify a nonce token against an action.
     *
     * @param string $token  Token string to verify.
     * @param string $action Base action identifier.
     * @param bool   $strict If true, rejects tokens from the previous 12h window.
     * @return bool True if valid within the required timeframe.
     */
    public function verify(string $token, string $action, bool $strict = false): bool;

    /**
     * Verify and consume a single-use token.
     * Deletes the transient immediately upon successful validation.
     *
     * @param string $token  Token string to check.
     * @param string $action Base action identifier.
     * @return bool True if valid and successfully consumed.
     */
    public function verifyOnce(string $token, string $action): bool;

    /**
     * Verify a nonce and return a detailed validation report.
     * Useful for REST APIs, custom error handling, and debugging.
     *
     * @param string $token  Token string to verify.
     * @param string $action Base action identifier.
     * @param bool   $strict Strict time window verification.
     * @return array{valid: bool, expired: bool, mismatch: bool, remaining_seconds: int, action: string}
     */
    public function verifyWithReport(string $token, string $action, bool $strict = false): array;

    /**
     * Check if a token is expired without full cryptographic verification.
     * Useful for UI hints and lightweight validation.
     *
     * @param string $token  Token string to check.
     * @param string $action Base action identifier.
     * @return bool True if token is expired.
     */
    public function isExpired(string $token, string $action): bool;

    /**
     * Get the remaining time in seconds until the token expires.
     * Useful for UI countdown timers and proactive session refreshes.
     *
     * @param string $token  Token string to check.
     * @param string $action Base action identifier.
     * @return int Seconds remaining until expiration (0 if expired or invalid).
     */
    public function getTimeRemaining(string $token, string $action): int;

    /**
     * Get a comprehensive security validation report.
     * Includes token status, environment checks (SSL, Origin, Referer, Session), and tick info.
     *
     * @param string $token  Token string to analyze.
     * @param string $action Base action identifier.
     * @return array{
     *     valid: bool,
     *     expired: bool,
     *     ssl_ok: bool,
     *     origin_ok: bool,
     *     referer_ok: bool,
     *     user_logged_in: bool,
     *     tick: int,
     *     message: string
     * }
     */
    public function getSecurityReport(string $token, string $action): array;

    // =========================================================================
    // OUTPUT & FRONTEND INTEGRATION
    // =========================================================================

    /**
     * Output a hidden nonce field for HTML forms.
     *
     * @param string $action Base action identifier.
     * @param string $name   Input name attribute.
     * @return void
     */
    public function field(string $action, string $name = '_wpnonce'): void;

    /**
     * Append a nonce query parameter to a URL.
     *
     * @param string $action Base action identifier.
     * @param string $url    Target base URL.
     * @param string $name   Query parameter name.
     * @return string URL with the nonce appended.
     */
    public function url(string $action, string $url, string $name = '_wpnonce'): string;

    /**
     * Generate an HTML attribute string with the nonce value.
     * Ideal for modern frontend frameworks (React/Vue/Alpine) where hidden inputs are not used.
     *
     * @param string $action Base action identifier.
     * @param string $name   Attribute name (e.g., 'data-nonce').
     * @return string Complete HTML attribute string (e.g., `data-nonce="abc123"`).
     */
    public function attribute(string $action, string $name = 'data-nonce'): string;

    /**
     * Localize script with the nonce token automatically.
     * Wrapper around wp_localize_script or wp_add_inline_script to inject nonce into JS.
     *
     * @param string $action Base action identifier.
     * @param string $handle Script handle to localize.
     * @param string $name   JS variable name.
     * @return void
     */
    public function localize(string $action, string $handle, string $name = 'nonce'): void;

    // =========================================================================
    // REQUEST EXTRACTION & CHECKING
    // =========================================================================

    /**
     * Extract nonce token from current request.
     * Checks $_REQUEST, JSON body, multipart data, and HTTP headers.
     *
     * @param string $name Expected token key or header name.
     * @return string Resolved token or empty string if not found.
     */
    public function extractFromRequest(string $name = '_wpnonce'): string;

    /**
     * Safely extract nonce from a URL query string.
     * Complements url() generation and replaces manual parse_url() parsing.
     *
     * @param string $url    The URL containing the nonce parameter.
     * @param string $name   Expected query parameter name.
     * @return string Resolved token or empty string if not found.
     */
    public function extractFromUrl(string $url, string $name = '_wpnonce'): string;

    /**
     * Check nonce from current request data.
     * Uses extractFromRequest() internally with header fallback.
     *
     * @param string $action Base action identifier.
     * @param string $name   Expected request key name.
     * @param bool   $strict Strict time window verification.
     * @return bool True if valid token is present.
     */
    public function checkRequest(string $action, string $name = '_wpnonce', bool $strict = false): bool;

    /**
     * Explicit HTTP Referer validation.
     * Decoupled from nonce verification for flexible security policies.
     *
     * @param bool $strict If true, requires exact admin URL match.
     * @return bool True if referer is valid or strict mode is disabled.
     */
    public function checkReferer(bool $strict = false): bool;

    /**
     * Validate HTTP Origin header against site URL.
     * Decoupled from Referer. Ideal for CORS, AJAX, and mobile apps.
     *
     * @param bool $strict If true, requires exact match including port and scheme.
     * @return bool True if origin is valid or strict mode is disabled.
     */
    public function validateOrigin(bool $strict = false): bool;

    // =========================================================================
    // GUARDS & FLOW CONTROL
    // =========================================================================

    /**
     * Set a custom failure handler for guard() and guardRedirect().
     * Chainable. Replaces rigid wp_die() or exit.
     *
     * @param callable $handler Function(string $action, array $report): void
     * @return self
     */
    public function onFailure(callable $handler): self;

    /**
     * Enable strict security mode: nonce + referer + user session + 12h window.
     * Chainable.
     *
     * @param bool $strict Enable or disable strict validation.
     * @return self
     */
    public function setStrictMode(bool $strict): self;

    /**
     * Enforce HTTPS requirement for nonce verification.
     * Chainable. Replaces manual is_ssl() checks in business logic.
     *
     * @param bool $require Enable or disable SSL enforcement.
     * @return self
     */
    public function requireSsl(bool $require = true): self;

    /**
     * Fail-fast guard. Throws exception or calls onFailure handler.
     *
     * @param string $action Base action identifier.
     * @param bool   $strict Strict time window verification.
     * @return void
     * @throws \RuntimeException If verification fails and no handler is set.
     */
    public function guard(string $action, bool $strict = false): void;

    /**
     * Safe redirect on nonce failure. Terminates script execution.
     *
     * @param string $action   Base action identifier.
     * @param string $fallback Fallback URL to redirect to.
     * @param bool   $strict   Strict time window verification.
     * @return void
     */
    public function guardRedirect(string $action, string $fallback, bool $strict = false): void;

    // =========================================================================
    // PREFIX MANAGEMENT (Minimal for Nonces)
    // =========================================================================

    /**
     * Get the currently active prefix for nonce actions.
     *
     * @return string The active prefix string.
     */
    public function getPrefix(): string;

    /**
     * Set a new base prefix. Nonces do not require scope stacks; this replaces it directly.
     *
     * @param string $prefix The new prefix. Empty string disables auto-prefixing.
     * @return void
     */
    public function setPrefix(string $prefix): void;

    /**
     * Resolve and normalize an action string with the current prefix without generating a token.
     * Useful for manual debugging, logging, or external integrations.
     *
     * @param string $action The raw action identifier.
     * @return string The prefixed, normalized, and validated action string.
     * @throws \InvalidArgumentException If action is empty or invalid after normalization.
     */
    public function resolveAction(string $action): string;

    // =========================================================================
    // ADVANCED LIFECYCLE & MAINTENANCE
    // =========================================================================

    /**
     * Revoke all active tokens for a specific action.
     * Clears transients and busts WP nonce cache immediately.
     *
     * @param string $action Base action identifier.
     * @return void
     */
    public function revokeAction(string $action): void;

    /**
     * Rotate nonce: generate new token, optionally invalidate the old one.
     * Ideal for SPA long-lived sessions.
     *
     * @param string $action        Base action identifier.
     * @param bool   $invalidateOld If true, revokes current action tokens.
     * @return string New token string.
     */
    public function rotate(string $action, bool $invalidateOld = true): string;

    /**
     * Safely migrate nonce context during plugin refactoring or namespace changes.
     * Copies tick state, invalidates old action tokens, and registers new mapping.
     *
     * @param string $from Old action identifier.
     * @param string $to   New action identifier.
     * @return void
     */
    public function migrateAction(string $from, string $to): void;

    /**
     * Purge expired transients and nonce cache entries.
     * Safe for CLI workers and long-running processes.
     *
     * @param int|null $beforeTimestamp Remove items that expired BEFORE this timestamp.
     *                                  Null means current time(). Useful for cron, audit, or dry-run strategies.
     * @return int Number of cleaned items.
     */
    public function clearExpired(?int $beforeTimestamp = null): int;

    // =========================================================================
    // BATCH & CONTEXT UTILITIES
    // =========================================================================

    /**
     * Generate nonce tokens for multiple actions at once.
     * Useful for SPA bootstrapping, complex forms, and headless integrations.
     *
     * @param array<string> $actions List of action identifiers.
     * @return array<string, string> Map of action => token.
     */
    public function batchCreate(array $actions): array;

    /**
     * Get current WP nonce tick value (12h/24h cycle).
     * Low-level method for debugging, external sync, and test mocking.
     *
     * @return int Current tick integer.
     */
    public function getTick(): int;
}