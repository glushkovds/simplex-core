<?php

namespace Simplex\Core\Models;

use Simplex\Core\ModelBase;

/**
 * Class UserRole
 * @package Simplex\Core\Models
 * @property string $name;
 */
class UserRole extends ModelBase
{
    protected static $table = 'user_role';
    protected static $primaryKeyName = 'role_id';

}