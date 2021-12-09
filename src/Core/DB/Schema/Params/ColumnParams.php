<?php
namespace Simplex\Core\DB\Schema\Params;

class ColumnParams
{
    public $isNull = true;
    public $default = null;
    public $autoIncrement = false;
    public $comment = '';
    public $collate = '';
    public $isPrimaryKey = false;
}