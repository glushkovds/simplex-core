<?php

namespace Simplex\Core\Models;

use Simplex\Core\Buffer;
use Simplex\Core\DB;
use Simplex\Core\ModelBase;

/**
 * Class User
 * @package Simplex\Core\Models
 * @property int $roleId
 * @property-read UserRole $role
 * @property string $login
 */
class User extends ModelBase
{
    protected static $table = 'user';
    protected static $primaryKeyName = 'user_id';

    protected function offsetGetRole()
    {
        return new UserRole($this->roleId);
    }

    /**
     * @param int|string $priv User privilege id or name
     * @return bool
     */
    public function ican($priv)
    {
        $privileges = $this->getPrivileges();
        if (is_int($priv)) {
            return isset($privileges['ids'][$priv]);
        }
        return isset($privileges['names'][$priv]);
    }

    /**
     * @return array ids => 1,2,3, names => priv1, priv2
     */
    public function getPrivileges()
    {
        return Buffer::getOrSet('user-priv-' . $this->id, function () {
            $q = "
                SELECT priv_id, name
                FROM user_priv
                WHERE active=1
                AND (
                    priv_id IN(SELECT priv_id FROM user_role_priv WHERE role_id" . ($this->role_id ? '=' . (int)$this->role_id : " IS NULL") . ")
                    OR priv_id IN(SELECT priv_id FROM user_priv_personal WHERE user_id=" . $this->id . ")
                )
            ";
            $r = DB::query($q);
            $ids = $names = [];
            while ($row = DB::fetch($r)) {
                $ids[(int)$row['priv_id']] = (int)$row['priv_id'];
                $names[$row['name']] = $row['name'];
            }
            return compact('ids', 'names');
        });
    }

    /**
     * @example User::findAdv()->modify('hasPrivileges', ['priv_name'])->all()
     * @param DB\AQ $AQ
     * @param array $privileges
     * @return void
     */
    public static function aqModifyHasPrivileges(DB\AQ $AQ, array $privileges)
    {
        if (empty($privileges)) {
            return;
        }
        // Allow multiple invoke this modifier
        $tableSuffix = str_replace('.', '', microtime(true)) . rand(1000000, 9999999);
        $table = static::$table;
        $AQ->where("
        $table.user_id IN(
            SELECT user_id FROM $table
            JOIN user_priv_personal AS user_priv_personal_$tableSuffix USING(user_id)
            JOIN user_role AS user_role_$tableSuffix 
                ON user_role_$tableSuffix.role_id = $table.role_id AND user_role_$tableSuffix.active = 1
            JOIN user_role_priv AS user_role_priv_$tableSuffix ON user_role_priv_$tableSuffix.role_id = user_role_$tableSuffix.role_id
            JOIN user_priv AS user_priv_$tableSuffix 
                ON user_priv_$tableSuffix.priv_id=COALESCE(user_priv_personal_$tableSuffix.priv_id,user_role_priv_$tableSuffix.priv_id) 
                AND user_priv_$tableSuffix.name IN('" . implode("','", DB::escape($privileges)) . "')
        )
        ");
    }
}