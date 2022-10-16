<?php

namespace Simplex\Core\DB;


use Simplex\Core\DB;
use Simplex\Core\ModelBase;

/**
 * Class AQ
 * Simplex Framework DataBase Active Query
 */
class AQ
{

    protected $select = '*';
    protected $from;
    protected $where = '';
    protected $orderBy = '';
    protected $limit = '';
    protected $asArray = false;
    /** @var  ModelBase|null */
    protected $modelClass;

    /** @var string|int|null {column name} or {column index in query result} for asScalar functionality */
    protected $scalarColumn = null;

    /**
     * @param bool $asArray
     * @return $this
     */
    public function asArray(bool $asArray = true)
    {
        $this->asArray = $asArray;
        $this->scalarColumn = null;
        return $this;
    }

    /**
     * @param string|array $fields
     * @return $this
     */
    public function select($fields)
    {
        $this->select = $fields;
        return $this;
    }

    /**
     * @param string $table
     * @return $this
     */
    public function from($table)
    {
        $this->from = $table;
        return $this;
    }

    /**
     * @param string|array|Where $where
     * @return $this
     */
    public function where($where)
    {
        $this->where = $where;
        return $this;
    }

    /**
     * @param string|array|Where $where
     * @return $this
     */
    public function andWhere($where)
    {
        $this->where = new Where($this->where);
        $this->where->add($where);
        return $this;
    }

    /**
     * @param string $orderBy
     * @return $this
     */
    public function orderBy($orderBy)
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    /**
     * @param string $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param string $class
     * @return $this
     */
    public function setModelClass(string $class)
    {
        $this->modelClass = $class;
        return $this;
    }

    protected function getSelect()
    {
        if (is_array($this->select)) {
            return implode(', ', $this->select);
        }
        if (is_string($this->select)) {
            return $this->select;
        }
        throw new \Exception('Bad select statement');
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function build()
    {
        if ($this->modelClass) {
            foreach ($this->modelClass::aqModifiersDefault() as $modifier) {
                $this->modify($modifier);
            }
        }
        if (empty($this->from) || !is_string($this->from)) {
            throw new \Exception('Bad from statement');
        }
        $q[] = 'SELECT ' . $this->getSelect();
        $q[] = "FROM `$this->from`";
        $q[] = new Where($this->where);
        if ($orderBy = (string)$this->orderBy) {
            $q[] = 'ORDER BY ' . DB::escape($orderBy);
        }
        if ($limit = (string)$this->limit) {
            $q[] = 'LIMIT ' . DB::escape($limit);
        }
        return implode(' ', $q);
    }

    /**
     * @param null|string $column If null query will return primary key
     * @return AQ
     * @throws \Exception
     * @deprecated use fetchScalar
     */
    public function selectColumn($column = null)
    {
        if ($column === null) {
            if (empty($this->modelClass)) {
                throw new \Exception('Model class not specified');
            }
            $column = $this->modelClass::getPrimaryKeyName();
        }
        return $this->select($column)->asScalar($column);
    }

    /**
     * @param int|string $column
     * @return $this
     * @deprecated use fetchScalar
     */
    public function asScalar($column = 0)
    {
        $this->asArray = false;
        $this->scalarColumn = $column;
        return $this;
    }

    /**
     * @param int|string $column
     * @return string|int|null
     * @throws \Exception
     */
    public function fetchScalar($column = 0)
    {
        $q = $this->build();
        $r = DB::query($q);
        $row = DB::fetch($r);
        if (is_int($column)) {
            $row = array_values($row);
        }
        if ($row && !array_key_exists($column, $row)) {
            throw new \Exception("Column $column does not exist in fetched row");
        }
        return $row[$column];
    }

    /**
     * @param int|string $column
     * @return ModelBase|null
     * @throws \Exception
     */
    public function fetchOne()
    {
        $q = $this->build();
        $r = DB::query($q);
        $row = DB::fetch($r);
        if (!$row) {
            return null;
        }
        if ($this->asArray) {
            return $row;
        }
        if (empty($this->modelClass)) {
            throw new \Exception('Model class not specified');
        }
        return (new $this->modelClass)->fill($row);
    }

    /**
     * @param string|array|null $assocKey
     * @return array| ModelBase[]
     * @throws \Exception
     */
    public function all($assocKey = null)
    {

        if ((is_bool($assocKey) || !$this->asArray && !$this->scalarColumn) && empty($this->modelClass)) {
            throw new \Exception('Model class not specified');
        }
        $q = $this->build();
        $r = DB::query($q);
        $result = [];
        $counter = 0;
        while ($row = DB::fetch($r)) {
            $firstIteration = $counter == 0;
            if ($this->asArray) {
                $payload = $row;
            } elseif ($this->scalarColumn !== null) {
                if (is_int($this->scalarColumn)) {
                    $payload = array_values($row)[$this->scalarColumn];
                } else {
                    $payload = $row[$this->scalarColumn];
                }
            } else {
                $payload = (new $this->modelClass)->fill($row);
            }

            if ($assocKey === true) {
                $result[$row[$this->modelClass::getPrimaryKeyName()]] = $payload;
            } elseif (is_array($assocKey)) {
                $assocKey = array_values($assocKey);
                switch (count($assocKey)) {
                    case 1:
                        $result[$row[$assocKey[0]]] = $payload;
                        break;
                    case 2:
                        $result[$row[$assocKey[0]]][$row[$assocKey[1]]] = $payload;
                        break;
                    case 3:
                        $result[$row[$assocKey[0]]][$row[$assocKey[1]]][$row[$assocKey[2]]] = $payload;
                        break;
                    case 0:
                    default:
                        $result[] = $payload;
                }
            } elseif (is_string($assocKey)) {
                $result[$row[$assocKey]] = $payload;
            } else {
                $result[] = $payload;
            }
            $counter++;
        }
        return $result;
    }

    public function __toString()
    {
        return $this->build();
    }

    /**
     * @see Core/DB/HowTo/UsingModifiers.md
     * @param string $modifier
     * @param ...$params
     * @return $this
     * @throws \Exception
     */
    public function modify(string $modifier, ...$params)
    {
        if (empty($this->modelClass)) {
            throw new \Exception("Model class must be specified for modifier $modifier");
        }
        $modifierAction = 'aqModify' . ucfirst($modifier);
        if (!method_exists($this->modelClass, $modifierAction)) {
            throw new \Exception("There is no modifier $modifier in $this->modelClass");
        }
        $this->modelClass::$modifierAction($this, ...$params);
        return $this;
    }

}

