<?php declare(strict_types=1);

namespace Frescoref\Woplucore\Contracts;

/**
 * Interface Styleable
 *
 * Lean CSS asset manager. Solves: inline CSS injection, media/conditional rules,
 * RTL mirroring, dependency control, and safe registration.
 * Direct wp_enqueue_style/wp_add_inline_style calls are STRICTLY FORBIDDEN.
 *
 * @package Frescoref\Woplucore\Contracts
 * @since 1.0.0
 */
interface Styleable
{
    /**
     * Register a stylesheet for later enqueueing.
     *
     * @param string        $handle  Unique handle.
     * @param string        $src     URL or relative path.
     * @param array<string> $deps    Dependency handles.
     * @param string|null   $version Auto-detected if null.
     * @param string        $media   Media attribute.
     * @return self
     */
    public function register(string $handle, string $src, array $deps = [], ?string $version = null, string $media = 'all'): self;

    /**
     * Mark registered style for output in the current request.
     *
     * @param string $handle Style handle.
     * @return self
     */
    public function enqueue(string $handle): self;

    /**
     * Dequeue and deregister style in one call.
     *
     * @param string $handle Style handle.
     * @return self
     */
    public function remove(string $handle): self;

    /**
     * Attach raw CSS via wp_add_inline_style().
     * Note: WP core does not support position control for inline styles.
     *
     * @param string $handle Target handle.
     * @param string $css    Raw CSS (without <style> tags).
     * @return self
     */
    public function inline(string $handle, string $css): self;

    /**
     * Add conditional loading rule via wp_style_add_data().
     * Supports media queries or legacy IE conditional comments.
     *
     * @param string $handle    Style handle.
     * @param string $condition e.g. '(min-width: 768px)' or 'lt IE 9'.
     * @return self
     */
    public function conditional(string $handle, string $condition): self;

    /**
     * Override media attribute for a registered stylesheet.
     *
     * @param string $handle Style handle.
     * @param string $query  Media query string.
     * @return self
     */
    public function media(string $handle, string $query): self;

    /**
     * Enable RTL mirroring for a stylesheet. Uses wp_style_add_data('rtl', true).
     *
     * @param string $handle Style handle.
     * @param bool $enable Enable/disable RTL.
     * @return self
     */
    public function rtl(string $handle, bool $enable = true): self;

    /**
     * Merge additional dependencies into existing registered stylesheet.
     *
     * @param string        $handle Style handle.
     * @param array<string> $deps   Additional dependency handles.
     * @return self
     */
    public function addDeps(string $handle, array $deps): self;

    /**
     * Set a loading condition for the next chained operation.
     *
     * @param callable $condition Function(): bool
     * @return self
     */
    public function when(callable $condition): self;

    /**
     * Set an inverse loading condition for the next chained operation.
     *
     * @param callable $condition Function(): bool
     * @return self
     */
    public function unless(callable $condition): self;

    /**
     * Resolve version from file modification time.
     *
     * @param string $src File path or URL.
     * @return string|null
     */
    public function autoVersion(string $src): ?string;
}