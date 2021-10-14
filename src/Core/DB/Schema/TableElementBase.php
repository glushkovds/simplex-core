<?php
namespace Simplex\Core\DB\Schema;

abstract class TableElementBase extends ElementBase
{
    /** @var ?\Simplex\Core\DB\Schema\Table */
    protected $table = null;

    public function getTable(): ?Table
    {
        return $this->table;
    }
}