<?php

namespace Simplex\Core\Identity\Models;

use Simplex\Core\ModelBase;

/**
 * Class UserRole
 * @package Simplex\Core\Identity\Models
 * @property string $name;
 */
class UserRole extends ModelBase
{
    protected static $table = 'user_role';
    protected static $primaryKeyName = 'role_id';

}