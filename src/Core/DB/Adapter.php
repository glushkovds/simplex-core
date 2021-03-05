<?php

namespace Simplex\Core\DB;

interface Adapter
{
    /**
     * Sets up connection to a database
     *
     * @return bool Connection status
     */
    public function connect(): bool;

    /**
     * Bounds array items to the query
     *
     * @param array $vars Items to bind
     * @return void
     */
    public function bind(array $vars);

    /**
     * Executes a SQL query with previously (optional) bound items
     *
     * @param string $q SQL query
     * @param array $vars Vars for prepared statement
     * @return mixed False if fails, adapter-specific object containing result
     */
    public function query(string $q, array $vars = []);

    /**
     * Fetches current row
     *
     * @param mixed $result Adapter-specific result object
     * @return mixed False if fails, assoc-array row if success
     */
    public function fetch(&$result);

    /**
     * Seeks result to a specific index
     *
     * @param mixed $result Adapter-specific result object
     * @param int $index Index to seek to (0 <= $index <= rowCount)
     * @return bool True on success, false on failure
     */
    public function seek(&$result, int $index): bool;

    /**
     * Returns value for specific field
     *
     * @param mixed $result Adapter-specific result object
     * @param int|string $field Column name/index
     * @return mixed False if fails, value on success
     */
    public function result($result, $field);

    /**
     * Makes custom assoc-array
     *
     * @param mixed $result Adapter-specific result object
     * @param bool|string $f1 Column name to use as key or false to use index
     * @param bool|string $f2 Second column name for inner rows
     * @return mixed Array containing results
     */
    public function assoc(&$result, $f1, $f2);

    /**
     * Returns last insert ID
     *
     * @return string Last insert ID
     */
    public function insertId(): string;

    /**
     * Returns last SQL error code
     *
     * @return string Last error code
     */
    public function errno(): ?string;

    /**
     * Returns error string or null if error code was not specified by the adapter
     *
     * @return string|null Error message
     */
    public function error(): ?string;

    /**
     * Returns formatted error message
     *
     * @return string Error message
     */
    public function errorPrepared(): string;

    /**
     * Escapes string
     *
     * @param string $str String to escape
     * @return string Escaped string
     */
    public function escape(string $str): string;

    /**
     * Returns amount of affected rows by the last executed statement
     *
     * @return int Row count
     */
    public function affectedRows(): int;

    /**
     * Begins transaction
     *
     * @return void
     */
    public function beginTransaction();

    /**
     * Commits transaction
     *
     * @return void
     */
    public function commitTransaction();

    /**
     * Rollbacks transaction
     *
     * @return void
     */
    public function rollbackTransaction();
}