<?php declare(strict_types=1);

namespace Frescoref\Woplucore\Utils;

use Frescoref\Woplucore\DatabaseManager;

/**
 * Class Database
 *
 * @package Frescoref\Woplucore\Utils
 * @since 1.0.0
 */
class Database
{
    /**
     * Resolved manager instance.
     *
     * @var DatabaseManager|null
     */
    private static $instance = null;

    /**
     * Resolve underlying manager instance. Creates default fallback if not configured.
     *
     * @return DatabaseManager Resolved database manager.
     */
    private static function instance(): DatabaseManager
    {
        if (null === self::$instance) {
            self::$instance = new DatabaseManager();
        }
        return self::$instance;
    }

    /** {@inheritDoc DatabaseManager::table()} */
    public static function table(string $name): string { return self::instance()->table($name); }

    /** {@inheritDoc DatabaseManager::prepare()} */
    public static function prepare(string $sql, ...$args): string { return self::instance()->prepare($sql, ...$args); }

    /** {@inheritDoc DatabaseManager::getResults()} */
    public static function getResults(string $sql): array { return self::instance()->getResults($sql); }

    /** {@inheritDoc DatabaseManager::getRow()} */
    public static function getRow(string $sql): ?array { return self::instance()->getRow($sql); }

    /** {@inheritDoc DatabaseManager::getVar()} */
    public static function getVar(string $sql) { return self::instance()->getVar($sql); }

    /** {@inheritDoc DatabaseManager::getColumn()} */
    public static function getColumn(string $sql): array { return self::instance()->getColumn($sql); }

    /** {@inheritDoc DatabaseManager::execute()} */
    public static function execute(string $sql) { return self::instance()->execute($sql); }

    /** {@inheritDoc DatabaseManager::insert()} */
    public static function insert(string $table, array $data, array $formats = []) { return self::instance()->insert($table, $data, $formats); }

    /** {@inheritDoc DatabaseManager::update()} */
    public static function update(string $table, array $data, array $where, array $dataFormats = [], array $whereFormats = []) { return self::instance()->update($table, $data, $where, $dataFormats, $whereFormats); }

    /** {@inheritDoc DatabaseManager::delete()} */
    public static function delete(string $table, array $where, array $formats = []) { return self::instance()->delete($table, $where, $formats); }

    /** {@inheritDoc DatabaseManager::insertBatch()} */
    public static function insertBatch(string $table, array $rows, array $formats = []) { return self::instance()->insertBatch($table, $rows, $formats); }

    /** {@inheritDoc DatabaseManager::count()} */
    public static function count(string $table, array $where = [], array $formats = []): int { return self::instance()->count($table, $where, $formats); }

    /** {@inheritDoc DatabaseManager::exists()} */
    public static function exists(string $table, array $where = [], array $formats = []): bool { return self::instance()->exists($table, $where, $formats); }

    /** {@inheritDoc DatabaseManager::tableExists()} */
    public static function tableExists(string $name): bool { return self::instance()->tableExists($name); }

    /** {@inheritDoc DatabaseManager::getColumnNames()} */
    public static function getColumnNames(string $name): array { return self::instance()->getColumnNames($name); }

    /** {@inheritDoc DatabaseManager::transaction()} */
    public static function transaction(callable $callback) { return self::instance()->transaction($callback); }

    /** {@inheritDoc DatabaseManager::lastInsertId()} */
    public static function lastInsertId(): int { return self::instance()->lastInsertId(); }

    /** {@inheritDoc DatabaseManager::affectedRows()} */
    public static function affectedRows(): int { return self::instance()->affectedRows(); }

    /** {@inheritDoc DatabaseManager::lastError()} */
    public static function lastError(): string { return self::instance()->lastError(); }
}