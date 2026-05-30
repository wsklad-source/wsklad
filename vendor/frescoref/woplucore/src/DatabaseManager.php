<?php declare(strict_types=1);

namespace Frescoref\Woplucore;

use Frescoref\Woplucore\Contracts\Databasable;

/**
 * Class DatabaseManager
 *
 * @package Woplucore
 * @since 1.0.0
 */
class DatabaseManager implements Databasable
{
    /**
     * Reference to the WordPress database object.
     * Initialized once in constructor to avoid global leakage.
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * Constructor.
     *
     * Captures the global $wpdb instance safely during bootstrap.
     */
    public function __construct()
    {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    // =========================================================================
    // TABLE & PREPARATION
    // =========================================================================

    /** {@inheritDoc} */
    public function table(string $name): string
    {
        $prefix = $this->wpdb->prefix;

        // Already prefixed or empty prefix
        if ('' === $prefix || 0 === \strpos($name, $prefix)) {
            return $name;
        }

        // Check if it's a core WP table property (e.g., 'posts', 'users')
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
        if (isset($this->wpdb->$name)) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
            return $this->wpdb->$name;
        }

        return $prefix . $name;
    }

    /** {@inheritDoc} */
    public function prepare(string $sql, ...$args): string
    {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        return $this->wpdb->prepare($sql, ...$args);
    }

    // =========================================================================
    // READ OPERATIONS (SELECT)
    // =========================================================================

    /** {@inheritDoc} */
    public function getResults(string $sql): array
    {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $results = $this->wpdb->get_results($sql, ARRAY_A);
        return \is_array($results) ? $results : [];
    }

    /** {@inheritDoc} */
    public function getRow(string $sql): ?array
    {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $row = $this->wpdb->get_row($sql, ARRAY_A);
        return \is_array($row) ? $row : null;
    }

    /** {@inheritDoc} */
    public function getVar(string $sql)
    {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $this->wpdb->get_var($sql);
    }

    /** {@inheritDoc} */
    public function getColumn(string $sql): array
    {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $results = $this->wpdb->get_col($sql);
        return \is_array($results) ? $results : [];
    }

    // =========================================================================
    // WRITE OPERATIONS (DML)
    // =========================================================================

    /** {@inheritDoc} */
    public function execute(string $sql)
    {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $this->wpdb->query($sql);
    }

    /** {@inheritDoc} */
    public function insert(string $table, array $data, array $formats = [])
    {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $this->wpdb->insert($this->table($table), $data, $formats);
    }

    /** {@inheritDoc} */
    public function update(string $table, array $data, array $where, array $dataFormats = [], array $whereFormats = [])
    {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $this->wpdb->update($this->table($table), $data, $where, $dataFormats, $whereFormats);
    }

    /** {@inheritDoc} */
    public function delete(string $table, array $where, array $formats = [])
    {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $this->wpdb->delete($this->table($table), $where, $formats);
    }

    /** {@inheritDoc} */
    public function insertBatch(string $table, array $rows, array $formats = [])
    {
        if (empty($rows)) {
            return 0;
        }

        $firstRow = \reset($rows);
        $columns  = \array_keys($firstRow);
        $columns  = \array_map(function ($col) { return '`' . $col . '`'; }, $columns);

        $values = [];
        $placeholders = [];

        foreach ($rows as $row) {
            // Validate column consistency
            if (\array_keys($row) !== $columns) {
                throw new \InvalidArgumentException('Batch insert rows must have identical column keys.');
            }

            $rowPlaceholders = [];
            foreach ($row as $col => $val) {
                $values[] = $val;
                $rowPlaceholders[] = !empty($formats[$col]) ? $formats[$col] : $this->detectFormat($val);
            }
            $placeholders[] = '(' . \implode(', ', $rowPlaceholders) . ')';
        }

        $sql = 'INSERT INTO ' . $this->table($table) . ' (' . \implode(', ', $columns) . ') VALUES ' . \implode(', ', $placeholders);

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        $preparedSql = $this->wpdb->prepare($sql, $values);
        $result = $this->wpdb->query($preparedSql);

        return false !== $result ? (int) $result : 0;
    }

    // =========================================================================
    // HIGH-LEVEL HELPERS
    // =========================================================================

    /** {@inheritDoc} */
    public function count(string $table, array $where = [], array $formats = []): int
    {
        $clause = $this->buildWhereClause($where, $formats);
        $sql = 'SELECT COUNT(*) FROM ' . $this->table($table) . ($clause['sql'] ? ' ' . $clause['sql'] : '');

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        $preparedSql = $this->wpdb->prepare($sql, $clause['values']);
        return (int) $this->getVar($preparedSql);
    }

    /** {@inheritDoc} */
    public function exists(string $table, array $where = [], array $formats = []): bool
    {
        $clause = $this->buildWhereClause($where, $formats);
        $sql = 'SELECT 1 FROM ' . $this->table($table) . ($clause['sql'] ? ' ' . $clause['sql'] : '') . ' LIMIT 1';

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        $preparedSql = $this->wpdb->prepare($sql, $clause['values']);
        return null !== $this->getVar($preparedSql);
    }

    // =========================================================================
    // SCHEMA INTROSPECTION
    // =========================================================================

    /** {@inheritDoc} */
    public function tableExists(string $name): bool
    {
        $table = $this->table($name);
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $sql = $this->wpdb->prepare('SHOW TABLES LIKE %s', $table);
        return null !== $this->getVar($sql);
    }

    /** {@inheritDoc} */
    public function getColumnNames(string $name): array
    {
        $table = $this->table($name);
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $sql = $this->wpdb->prepare('SHOW COLUMNS FROM ' . $table);
        $columns = $this->getColumn($sql);
        return \array_values(\array_filter($columns, 'is_string'));
    }

    // =========================================================================
    // TRANSACTIONS & META
    // =========================================================================

    /** {@inheritDoc} */
    public function transaction(callable $callback)
    {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $this->wpdb->query('START TRANSACTION');

        try {
            $result = $callback($this);

            if (false === $result) {
                throw new \RuntimeException('Transaction callback returned false. Rolling back.');
            }

            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $this->wpdb->query('COMMIT');
            return $result;
        } catch (\Throwable $e) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $this->wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    /** {@inheritDoc} */
    public function lastInsertId(): int
    {
        return (int) $this->wpdb->insert_id;
    }

    /** {@inheritDoc} */
    public function affectedRows(): int
    {
        return (int) $this->wpdb->rows_affected;
    }

    /** {@inheritDoc} */
    public function lastError(): string
    {
        return (string) $this->wpdb->last_error;
    }

    // =========================================================================
    // INTERNAL HELPERS
    // =========================================================================

    /**
     * Detects SQL placeholder format based on PHP variable type.
     *
     * @param mixed $value
     * @return string %s, %d, or %f
     */
    private function detectFormat($value): string
    {
        if (\is_int($value)) {
            return '%d';
        }
        if (\is_float($value)) {
            return '%f';
        }
        return '%s';
    }

    /**
     * Builds a safe WHERE clause and extracts values for $wpdb->prepare().
     *
     * @param array<string, mixed> $where   Column => value conditions.
     * @param array<string, string> $formats Column => format overrides.
     * @return array{sql: string, values: array<int, mixed>} Prepared clause and flat values array.
     */
    private function buildWhereClause(array $where, array $formats = []): array
    {
        if (empty($where)) {
            return ['sql' => '', 'values' => []];
        }

        $clauses = [];
        $values  = [];

        foreach ($where as $column => $value) {
            // Handle NULL explicitly
            if (null === $value) {
                $clauses[] = '`' . $column . '` IS NULL';
                continue;
            }

            $format = !empty($formats[$column]) ? $formats[$column] : $this->detectFormat($value);
            $clauses[] = '`' . $column . '` = ' . $format;
            $values[]  = $value;
        }

        return [
            'sql'    => 'WHERE ' . \implode(' AND ', $clauses),
            'values' => $values,
        ];
    }
}