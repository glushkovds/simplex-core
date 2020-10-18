<?php

namespace Simplex\Core\DB;


use Simplex\Core\DB;

/**
 * Class ExprTypeSet
 * Syntax sugar for work with MySQL datatype SET
 * @example We need to remove 'value1' from field `tags` which value is 'value1,value2,value3':
 *          $model->update(['tags' => ExprTypeSet::remove('tags', 'value1')]);
 */
class ExprTypeSet extends Expr
{
    /**
     * @param string $fieldName
     * @param string $value
     * @return static
     */
    public static function add($fieldName, $value)
    {
        return new static("CONCAT(`$fieldName`,'," . DB::escape($value) . "')");
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @return static
     */
    public static function remove($fieldName, $value)
    {
        return new static("
            TRIM(BOTH ',' FROM
              REPLACE(
                REPLACE(CONCAT(',',REPLACE(`$fieldName`, ',', ',,'), ','),'," . DB::escape($value) . ",', ''), ',,', ','
              )
            )");
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @return static
     */
    public static function in($fieldName, $value)
    {
        return new static("FIND_IN_SET('" . DB::escape($value) . "',`$fieldName`)");
    }
}
