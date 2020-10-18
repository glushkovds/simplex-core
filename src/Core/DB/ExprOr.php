<?php

namespace Simplex\Core\DB;


/**
 * Class Or
 * Usage
 * $where = new Where(['param' => 1, new Or(['param2' => 2, 'param2' => 3]));
 * Model::findOne($where);
 * With inner Expr: Model::findOne(new Or(['param2' => 2, new Expr('param2 IN (4,5)'])));
 */
class ExprOr extends Expr
{
    public function __toString()
    {
        $values = [];
        foreach ($this->value as $key => $value) {
            $values[] = (new Where([$key => $value]))->toString(false);
        }
        return '(' . join(' OR ', $values) . ')';
    }
}