<?php
namespace Simplex\Core\DB\Schema\Params;

class TableParams
{
    public $ifNotExists = false;
    public $temporary = false;
    public $autoIncrement = 0;
    public $comment = '';
    public $collate = '';
    public $characterSet = '';
    public $defaultCharacterSet = false;
}