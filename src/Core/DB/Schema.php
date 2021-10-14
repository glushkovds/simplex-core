<?php
namespace Simplex\Core\DB;

use Simplex\Core\Container;
use Simplex\Core\DB;
use Simplex\Core\DB\Schema\Table;

class Schema
{
    /** @var \Simplex\Core\DB\Schema\Table[] Existing tables */
    protected $tables = [];

    /** @var \Simplex\Core\DB\Schema\Table[] Tables that await their creation (commit) */
    protected $awaitingCreate = [];

    /** @var \Simplex\Core\DB\Schema\Table[] Tables that await their deletion */
    protected $awaitingDelete = [];

    /** @var \Simplex\Core\DB\Schema\Table[] Tables that have been created while this object is alive */
    protected $sessionCreated = [];

    public function __construct()
    {
        $this->reload();
    }

    // ------------ UTIL ------------ //
    public function reload()
    {
        $dbName = Container::getConfig()::$db_name;

        // TODO: load full table structure

        $this->tables = [];

        $tables = DB::query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?', [$dbName]);
        while ($table = DB::fetch($tables)) {
            $tableName = $table['TABLE_NAME'];
            $this->tables[$tableName] = $this->loadTable($tableName);
        }
    }

    protected function loadTable(string $name): Table
    {
        $table = new Table($name, true);
        return $table;
    }

    public function hasTable(string $name): bool
    {
        return isset($this->tables[$name]);
    }

    // ------------ ALTERNATION ------------ //

    /**
     * @param string $name Table name
     * @param callable $fn Table data callback (Schema\Table passed as argument)
     */
    public function createTable(string $name, callable $fn)
    {
        $table = new Table($name);
        $fn($table);

        $this->awaitingCreate[] = $table;
    }

    /**
     * Drops table
     *
     * @throws \Exception
     */
    public function dropTable(string $name, bool $checkIfExists = false)
    {
        if ($checkIfExists && !$this->hasTable($name)) {
            throw new \Exception('Table ' . $name . ' does not exist');
        }

        $this->awaitingDelete[] = $this->tables[$name];
    }

    // ------------ EXECUTION ------------ //

    /**
     * Commits changes
     *
     * @return bool
     * @throws \Exception
     */
    public function commit(): bool
    {
        return $this->commitDeletion() && $this->commitCreation();
    }

    /**
     * Rollbacks created tables
     *
     * @return bool
     * @throws \Exception
     */
    public function rollback(): bool
    {
        foreach ($this->sessionCreated as $table) {
            $this->dropTable($table->getName());
        }

        return $this->commitDeletion();
    }

    protected function commitDeletion(): bool
    {
        // delete tables, no rollback available
        foreach ($this->awaitingDelete as $table) {
            $status = !!DB::query('DROP TABLE IF EXISTS `' . $table->getName() . '`');
            if (!$status) {
                return false;
            }
        }

        // clear pool
        $this->awaitingDelete = [];
        return true;
    }

    /**
     * Commits creation changes
     *
     * @return bool
     * @throws \Exception
     */
    protected function commitCreation(): bool
    {
        foreach ($this->awaitingCreate as $table) {
            // try creating the table
            $status = !!DB::query($table->toString());
            if (!$status) {
                return false;
            }

            // if everything went smooth, let's check if that table was already made
            $params = $table->getParams();
            if (!$params->ifNotExists || $params->ifNotExists && !$this->hasTable($table->getName())) {
                $this->sessionCreated[] = $table;
            }
        }

        // clear pool
        $this->awaitingCreate = [];
        return true;
    }
}