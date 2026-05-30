<?php
declare(strict_types=1);

namespace Frescoref\Woplucore\Contracts;

/**
 * Interface Databasable
 *
 * Secure, WP-native database abstraction layer.
 * Eliminates global $wpdb usage in business logic.
 * Provides auto-prefixing, prepared statements, safe CRUD helpers,
 * transaction support, schema introspection, and strict error handling.
 * Direct calls to $wpdb methods in business logic are STRICTLY FORBIDDEN.
 *
 * @package Frescoref\Woplucore\Contracts
 * @since 1.0.0
 */
interface Databasable
{
    // =========================================================================
    // TABLE & PREPARATION
    // =========================================================================

    /**
     * Resolve a short table name to its fully prefixed version.
     * Supports custom tables ('my_logs') and WP core tables ('posts', 'users').
     *
     * @param string $name Short table name.
     *
     * @return string Fully prefixed table name safe for queries.
     */
    public function table(string $name): string;

    /**
     * Safely prepare a SQL statement with arguments.
     * Uses WP's native prepare() under the hood.
     *
     * @param string $sql  SQL query with %s, %d, %f placeholders.
     * @param mixed  ...$args Arguments to bind.
     *
     * @return string Prepared SQL string.
     * @throws \InvalidArgumentException If placeholder/argument count mismatch.
     */
    public function prepare(string $sql, ...$args): string;

    // =========================================================================
    // READ OPERATIONS (SELECT)
    // =========================================================================

    /**
     * Fetch multiple rows as associative arrays.
     *
     * @param string $sql Prepared SELECT query.
     *
     * @return array<int, array<string, mixed>> Result set.
     */
    public function getResults(string $sql): array;

    /**
     * Fetch a single row as an associative array.
     *
     * @param string $sql Prepared SELECT query.
     *
     * @return array<string, mixed>|null Single row or null.
     */
    public function getRow(string $sql): ?array;

    /**
     * Fetch a single scalar value from the first column of the first row.
     *
     * @param string $sql Prepared SELECT query.
     *
     * @return mixed|null Scalar value or null.
     */
    public function getVar(string $sql);

    /**
     * Fetch a single column as a flat array of scalar values.
     * Ideal for IDs, slugs, or status lists.
     *
     * @param string $sql Prepared SELECT query (should return one column).
     *
     * @return array<int, mixed> Flat list of values.
     */
    public function getColumn(string $sql): array;

    // =========================================================================
    // WRITE OPERATIONS (DML)
    // =========================================================================

    /**
     * Execute a prepared non-SELECT statement (INSERT, UPDATE, DELETE, etc.).
     *
     * @param string $sql Prepared SQL query.
     *
     * @return int|false Number of affected rows, or false on error.
     */
    public function execute(string $sql);

    /**
     * Safe wrapper for $wpdb->insert().
     * Auto-detects formats if not provided.
     *
     * @param string $table   Prefixed table name.
     * @param array  $data    Column => value pairs.
     * @param array  $formats Optional column => format mapping (%s, %d, %f).
     *
     * @return int|false Insert ID on success, false on failure.
     */
    public function insert(string $table, array $data, array $formats = []);

    /**
     * Safe wrapper for $wpdb->update().
     *
     * @param string $table       Prefixed table name.
     * @param array  $data        Column => value pairs to update.
     * @param array  $where       Column => value conditions.
     * @param array  $dataFormats Optional formats for data.
     * @param array  $whereFormats Optional formats for where clause.
     *
     * @return int|false Number of affected rows, or false on failure.
     */
    public function update(string $table, array $data, array $where, array $dataFormats = [], array $whereFormats = []);

    /**
     * Safe wrapper for $wpdb->delete().
     *
     * @param string $table   Prefixed table name.
     * @param array  $where   Column => value conditions.
     * @param array  $formats Optional formats for where clause.
     *
     * @return int|false Number of deleted rows, or false on failure.
     */
    public function delete(string $table, array $where, array $formats = []);

    /**
     * Insert multiple rows in a single query.
     * Significantly faster than looped insert() for bulk imports.
     *
     * @param string $table   Prefixed table name.
     * @param array  $rows    Array of associative arrays (column => value).
     * @param array  $formats Column => format mapping applied to all rows.
     *
     * @return int|false Number of inserted rows, or false on failure.
     */
    public function insertBatch(string $table, array $rows, array $formats = []);

    // =========================================================================
    // HIGH-LEVEL HELPERS
    // =========================================================================

    /**
     * Count rows matching conditions safely.
     *
     * @param string $table   Prefixed table name.
     * @param array  $where   Column => value conditions.
     * @param array  $formats Optional formats for where clause.
     *
     * @return int Number of matching rows.
     */
    public function count(string $table, array $where = [], array $formats = []): int;

    /**
     * Check if at least one row matches conditions.
     * Optimized with LIMIT 1 / EXISTS logic.
     *
     * @param string $table   Prefixed table name.
     * @param array  $where   Column => value conditions.
     * @param array  $formats Optional formats for where clause.
     *
     * @return bool True if matching row exists.
     */
    public function exists(string $table, array $where = [], array $formats = []): bool;

    // =========================================================================
    // SCHEMA INTROSPECTION
    // =========================================================================

    /**
     * Check if a table exists in the database.
     * Safe alternative to SHOW TABLES for migrations, setup routines, and uninstall hooks.
     *
     * @param string $name Short or fully prefixed table name.
     *
     * @return bool True if table exists.
     */
    public function tableExists(string $name): bool;

    /**
     * Retrieve column names for a given table.
     * Useful for dynamic validation, form generation, schema diffs, or reflection-less migrations.
     *
     * @param string $name Short or fully prefixed table name.
     *
     * @return array<int, string> List of column names.
     */
    public function getColumnNames(string $name): array;

    // =========================================================================
    // TRANSACTIONS & META
    // =========================================================================

    /**
     * Execute a callback within a database transaction.
     * Automatically rolls back on exception, commits on success.
     * Requires InnoDB or transactional storage engine.
     *
     * @param callable $callback Function(\Frescoref\Woplucore\Contracts\Databasable $db): mixed
     *
     * @return mixed Result of the callback.
     * @throws \RuntimeException If transaction fails or callback throws.
     */
    public function transaction(callable $callback);

    /**
     * Get the last inserted ID.
     *
     * @return int Insert ID.
     */
    public function lastInsertId(): int;

    /**
     * Get the number of rows affected by the last query.
     *
     * @return int Affected rows count.
     */
    public function affectedRows(): int;

    /**
     * Retrieve the last database error message safely.
     *
     * @return string Error message or empty string if no error.
     */
    public function lastError(): string;
}