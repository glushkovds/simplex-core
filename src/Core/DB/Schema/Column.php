<?php
namespace Simplex\Core\DB\Schema;

use Simplex\Core\DB;
use Simplex\Core\DB\Schema\Params\ColumnParams;

class Column extends TableElementBase
{
    /** @var \Simplex\Core\DB\Schema\Params\ColumnParams  */
    protected $params;
    protected $type = '';

    public function __construct(string $name, ?Table $table = null)
    {
        $this->name = $name;
        $this->params = new ColumnParams();
        $this->table = $table;
    }

    // ------------ BUILDER ------------ //

    /**
     * Set data type
     *
     * @param string $type Type name
     * @param int|null $length Type length
     * @return $this
     */
    public function dataType(string $type, ?int $length = null): self
    {
        $this->type = $type;
        if ($length !== null) {
            $this->type .= '(' . $length . ')';
        }

        return $this;
    }

    public function setNull(bool $isNull = true): self
    {
        $this->params->isNull = $isNull;
        return $this;
    }

    public function setDefault($default): self
    {
        $this->params->default = $default;
        return $this;
    }

    public function autoIncrement(bool $v = true): self
    {
        $this->params->autoIncrement = $v;
        return $this;
    }

    public function comment(string $comment): self
    {
        $this->params->comment = $comment;
        return $this;
    }

    public function collate(string $collation): self
    {
        $this->params->collate = $collation;
        return $this;
    }

    // ------------ UTIL ------------ //

    /**
     * Returns params
     *
     * @return \Simplex\Core\DB\Schema\Params\ColumnParams
     */
    public function getParams(): ColumnParams
    {
        return $this->params;
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function toString(): string
    {
        if (!$this->name) {
            throw new \Exception("Invalid column name");
        }

        if (!$this->type) {
            throw new \Exception("Invalid data type");
        }

        $sql = DB::wrapName($this->name) . ' ' . $this->type . ' ';
        $sql .= $this->params->isNull ? 'NULL ' : 'NOT NULL ';

        if ($this->params->default) {
            $sql .= 'DEFAULT '
                . (is_string($this->params->default) ? DB::wrapString($this->params->default) : $this->params->default);
        }

        if ($this->params->autoIncrement) {
            $sql .= 'AUTO_INCREMENT ';
        }

        if ($this->params->comment) {
            $sql .= 'COMMENT ' . DB::wrapString($this->params->comment);
        }

        if ($this->params->collate) {
            $sql .= 'COLLATE ' . $this->params->collate;
        }

        return $sql;
    }
}