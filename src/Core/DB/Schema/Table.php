<?php
namespace Simplex\Core\DB\Schema;

use Simplex\Core\DB;
use Simplex\Core\DB\Schema\Params\TableParams;

class Table extends ElementBase
{
    /** @var \Simplex\Core\DB\Schema\Params\TableParams */
    protected $params;

    /** @var \Simplex\Core\DB\Schema\Element[] */
    protected $elements = [];

    protected $isExisting;

    public function __construct(string $name, bool $isExisting = false)
    {
        $this->params = new TableParams();
        $this->name = $name;
        $this->isExisting = $isExisting;
    }

    // ------------ BUILDER ------------ //

    /**
     * Create table only if one doesn't exist yet
     */
    public function ifNotExists(): self
    {
        $this->params->ifNotExists = true;
        return $this;
    }

    /**
     * Create temporary table
     */
    public function temporary(): self
    {
        $this->params->temporary = true;
        return $this;
    }

    /**
     * Set auto increment counter
     *
     * @param int $n
     * @return $this
     */
    public function autoIncrement(int $n): self
    {
        $this->params->autoIncrement = $n;
        return $this;
    }

    /**
     * Set table comment
     *
     * @param string $comment
     * @return $this
     */
    public function comment(string $comment): self
    {
        $this->params->comment = $comment;
        return $this;
    }

    /**
     * Set table character set
     *
     * @param string $charset Character set
     * @param bool $isDefault DEFAULT modifier
     * @return $this
     */
    public function characterSet(string $charset, bool $isDefault = false): self
    {
        $this->params->characterSet = $charset;
        $this->params->defaultCharacterSet = $isDefault;

        return $this;
    }

    /**
     * Adds a column
     *
     * @param string $name Column name
     * @param string $type Column type
     * @param int|null $length Column type length
     * @return \Simplex\Core\DB\Schema\Column
     */
    public function addColumn(string $name, string $type, ?int $length = null): Column
    {
        $col = new Column($name);
        $col->dataType($type, $length);

        $this->elements[$name] = $col;
        return $col;
    }

    /**
     * Adds a constraint
     *
     * @param string $name Constraint symbol name
     * @return \Simplex\Core\DB\Schema\Constraint
     */
    public function addConstraint(string $name): Constraint
    {
        $const = new Constraint($name);

        $this->elements[$name] = $const;
        return $const;
    }

    // ------------ UTIL ------------ //

    /**
     * Returns params
     *
     * @return \Simplex\Core\DB\Schema\Params\TableParams
     */
    public function getParams(): TableParams
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
            throw new \Exception('Invalid table name');
        }

        if (!$this->elements) {
            throw new \Exception('Table cannot be empty');
        }

        $sql = 'CREATE ';

        if ($this->params->temporary) {
            $sql .= 'TEMPORARY ';
        }

        $sql .= 'TABLE ';

        if ($this->params->ifNotExists) {
            $sql .= 'IF NOT EXISTS ';
        }

        $sql .= DB::wrapName($this->name) . ' (';

        $builtElements = [];
        foreach ($this->elements as $element) {
            $builtElements[] = $element->toString();
        }

        $sql .= implode(',', $builtElements) . ') ';

        if ($this->params->autoIncrement) {
            $sql .= 'AUTO_INCREMENT ' . $this->params->autoIncrement . ' ';
        }

        if ($this->params->comment) {
            $sql .= 'COMMENT ' . DB::wrapString($this->params->comment) . ' ';
        }

        if ($this->params->collate) {
            $sql .= 'COLLATE ' . $this->params->collate . ' ';
        }

        if ($this->params->characterSet) {
            $sql .= 'CHARSET ' . $this->params->characterSet . ' ';
        }

        return $sql;
    }

    public function getName(): string
    {
        return $this->name;
    }
}