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

    /**
     * @param string|ModelBase $table
     * @param string $joinColumn1
     * @param string|null $joinColumn2
     * @param string $type
     * @throws \Exception
     */
    public function __construct($table, string $joinColumn1, ?string $joinColumn2 = null, string $type = 'INNER')
    {
        $this->table = ($table instanceof ModelBase) ? $table::getTableName() : (string)$table;
        $this->joinColumn1 = $joinColumn1;
        $this->joinColumn2 = $joinColumn2;
        $type = strtoupper($type);
        if (!in_array($type, ['INNER', 'LEFT', 'RIGHT'])) {
            throw new \Exception('Join type must be one of INNER, LEFT, RIGHT');
        }
        $this->type = $type;
    }

    public function toSql(): string
    {
        $c1 = self::escapeColumn($this->joinColumn1);
        $sql = "$this->type JOIN `$this->table`";
        if ($this->joinColumn2) {
            $c2 = self::escapeColumn($this->joinColumn2);
            $sql .= " ON $c1 = $c2";
        } else {
            $sql .= " USING($c1)";
        }
        return $sql;
    }

    protected static function escapeColumn(string $column)
    {
        $parts = explode('.', $column);
        return array_reduce($parts, function ($part) {
            return "`$part`";
        });
    }
}