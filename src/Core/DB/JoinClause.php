<?php

namespace Simplex\Core\DB;

use Simplex\Core\ModelBase;

class JoinClause
{
    /** @var string */
    protected $table;
    /** @var string */
    protected $joinColumn1;
    /** @var string|null */
    protected $joinColumn2;
    /** @var string */
    protected $type;
    /** @var Where|null */
    protected $extraOnConditions;

    /**
     * @param string|ModelBase $table
     * @param string $joinColumn1
     * @param string|null $joinColumn2 if not specified, will be used USING clause
     * @param string $type for example INNER, LEFT, RIGHT, FULL
     * @param string|Where|array|null $extraOnConditions
     * @throws \Exception
     */
    public function __construct($table, string $joinColumn1, ?string $joinColumn2 = null, string $type = 'INNER', $extraOnConditions = null)
    {
        $this->table = (is_subclass_of($table, ModelBase::class)) ? $table::getTableName() : (string)$table;
        $this->joinColumn1 = $joinColumn1;
        $this->joinColumn2 = $joinColumn2;
        $type = strtoupper($type);
        if (!in_array($type, ['INNER', 'LEFT', 'RIGHT', 'FULL'])) {
            throw new \Exception('Join type must be one of INNER, LEFT, RIGHT, FULL');
        }
        $this->type = $type;
        $this->extraOnConditions = is_null($extraOnConditions) ? null : new Where($extraOnConditions);
    }

    public function toSql(string $leftTable): string
    {
        $sql = "$this->type JOIN `$this->table`";
        if (!$this->joinColumn2 && !$this->extraOnConditions) {
            $c1 = self::escapeColumn($this->joinColumn1);
            $sql .= " USING($c1)";
        } else {
            $c1 = self::prepareColumn($this->joinColumn1, $leftTable);
            $c2 = self::prepareColumn($this->joinColumn2 ?? $this->joinColumn1, $this->table);
            $sql .= " ON $c1 = $c2";
            if (
                $this->extraOnConditions
                && ($eoc = $this->extraOnConditions->toString(false))
            ) {
                $sql .= " AND $eoc";
            }
        }
        return $sql;
    }

    protected static function prepareColumn(?string $column, string $possibleTable): ?string
    {
        if (!$column) {
            return $column;
        }
        if (strpos($column, '.') === 0) {
            $column = "$possibleTable$column";
        }
        return static::escapeColumn($column);
    }

    protected static function escapeColumn(string $column): string
    {
        $parts = explode('.', $column);
        foreach ($parts as &$part) {
            $part = "`$part`";
        }
        return implode('.', $parts);
    }
}