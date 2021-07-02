<?php
namespace Simplex\Core\Models;

use Simplex\Core\ModelBase;

/**
 * Class Migration
 * @package Simplex\Core\Models
 *
 * @property int id
 * @property string file
 */
class Migration extends ModelBase
{
    protected static $table = 'migration';
    protected static $primaryKeyName = 'id';
}