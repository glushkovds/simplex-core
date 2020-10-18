<?php

namespace Simplex\Core\DB;


/**
 * Class Expr
 * Use instance of this class to unescape your statement
 */
class Expr
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return $this->value;
    }
}