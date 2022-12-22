<?php

namespace Simplex\Core\Models;

use Simplex\Core\ModelBase;

class ModelSettings extends ModelBase
{
    protected static $table = 'settings';
    protected static $primaryKeyName = 'setting_id';

    public static function get(string $key)
    {
        return static::findOne(['alias' => $key])['value'] ?? null;
    }
}