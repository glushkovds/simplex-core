<?php
namespace Simplex\Core\DB\Schema;

use Simplex\Core\DB;
use Simplex\Core\DB\Schema\Params\ConstraintParams;

class Constraint extends ElementBase
{
    /** @var \Simplex\Core\DB\Schema\Params\ConstraintParams */
    protected $params;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->params = new ConstraintParams();
    }

    // ------------ BUILDER ------------ //

    /**
     * Sets primary key constraint
     *
     * @param array|string $column Column(s) to index
     * @return $this
     */
    public function primaryKey($column): self
    {
        if (!is_array($column)) {
            $column = [$column];
        }

        $this->params->isUnique = $this->params->isForeign = false;
        $this->params->isPrimary = true;
        $this->params->keyParts = $column;

        return $this;
    }

    /**
     * Sets unique key/index constraint
     *
     * @param array|string $column Column(s) to index
     * @param bool $isIndex Should mark this constraint as index
     * @return $this
     */
    public function unique($column, bool $isIndex = false): self
    {
        if (!is_array($column)) {
            $column = [$column];
        }

        $this->params->isPrimary = $this->params->isForeign = false;
        $this->params->isUnique = true;
        $this->params->keyParts = $column;
        $this->params->isUniqueIndex = $isIndex;

        return $this;
    }

    /**
     * References column(s) in another table
     *
     * @param string $referenceTable Target reference table
     * @param string|array $colToReference Associative array or string of my_column => their_column
     * @return $this
     */
    public function foreignKey(string $referenceTable, $colToReference): self
    {
        $columns = [];
        $referenceColumns = [];

        if (is_array($colToReference)) {
            foreach ($colToReference as $column => $reference) {
                $columns[] = $column;
                $referenceColumns[] = $reference;
            }
        } else {
            $columns = $referenceColumns = [$colToReference];
        }

        $this->params->isUnique = $this->params->isPrimary = false;
        $this->params->isForeign = true;
        $this->params->reference = $referenceTable;
        $this->params->keyParts = $columns;
        $this->params->referenceColumns = $referenceColumns;

        return $this;
    }

    /**
     * Sets MATCH option for foreign key
     *
     * @param string $match ConstraintParams::REFERENCE_MATCH_{X}
     * @return $this
     */
    public function match(string $match): self
    {
        $this->params->match = $match;
        return $this;
    }

    /**
     * Sets ON DELETE option for foreign key
     *
     * @param string $option ConstraintParams::REFERENCE_OPTION_{X}
     * @return $this
     */
    public function onDelete(string $option): self
    {
        $this->params->onDelete = $option;
        return $this;
    }

    /**
     * Sets ON UPDATE option for foreign key
     *
     * @param string $option ConstraintParams::REFERENCE_OPTION_{X}
     * @return $this
     */
    public function onUpdate(string $option): self
    {
        $this->params->onUpdate = $option;
        return $this;
    }

    // ------------ UTIL ------------ //

    /**
     * @return \Simplex\Core\DB\Schema\Params\ConstraintParams
     */
    public function getParams(): ConstraintParams
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
            throw new \Exception('Constraint should have a symbol name');
        }

        if (!$this->params->isForeign && !$this->params->isPrimary && !$this->params->isUnique) {
            throw new \Exception('Unknown constraint type');
        }

        $sql = 'CONSTRAINT ' . $this->name . ' ';

        if ($this->params->isForeign) {
            $sql .= 'FOREIGN KEY ';
        } elseif ($this->params->isPrimary) {
            $sql .= 'PRIMARY KEY ';
        } elseif ($this->params->isUnique) {
            $sql .= 'UNIQUE ' . ($this->params->isUniqueIndex ? 'INDEX ' : 'KEY ');
        }

        $sql .= '(' . implode(',', $this->params->keyParts) . ') ';

        if ($this->params->isForeign) {
            $sql .= 'REFERENCES ' . DB::wrapName($this->params->reference) . ' ';
            $sql .= '(' . implode(',', $this->params->referenceColumns) . ') ';

            if ($this->params->match) {
                $sql .= 'MATCH ' . $this->params->match . ' ';
            }

            if ($this->params->onDelete) {
                $sql .= 'ON DELETE ' . $this->params->onDelete . ' ';
            }

            if ($this->params->onUpdate) {
                $sql .= 'ON UPDATE ' . $this->params->onUpdate . ' ';
            }
        }

        return $sql;
    }
}