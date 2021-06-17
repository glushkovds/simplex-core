<?php


namespace Simplex\Core;

/**
 * Class UserInstance
 * @package Simplex\Core
 * @deprecated use Container::getUser() and \Simplex\Core\Models\User and Simplex Auth package
 * @link https://github.com/glushkovds/simplex-auth
 */
class UserInstance
{

    public $id = 0;
    public $login = '';
    public $role_id = 0;
    public $role_name = '';
    private $priv_ids = array();
    private $priv_names = array();
    private $info;
    private $idName;
    private $hashName;
    private $dbHashName;
    private $remIdName;
    private $remHashName;

    /**
     *
     * @param type $idName
     * @param type $hashName
     * @param type $dbHashName
     * @param type $remIdName
     * @param type $remHashName
     */
    public function __construct($idName, $hashName, $dbHashName, $remIdName, $remHashName)
    {
        $this->idName = $idName;
        $this->hashName = $hashName;
        $this->dbHashName = $dbHashName;
        $this->remIdName = $remIdName;
        $this->remHashName = $remHashName;
        $this->login();
    }

    public function logout()
    {
        $_SESSION[$this->idName] = 0;
        $_SESSION[$this->hashName] = '';
        setcookie($this->remIdName);
        setcookie($this->remHashName);
        unset($_COOKIE[$this->remIdName]);
        unset($_COOKIE[$this->remHashName]);
    }
    
    public function initByModel(\Simplex\Core\Models\User $model){
        $this->id = $model->getId();
        $this->login = $model->login;
        $this->role_id = $model->roleId;
        $this->role_name = $model->role->name;
        $this->login();
    }

    private function login()
    {
        if (!isset($_SESSION[$this->idName]) && isset($_COOKIE[$this->remIdName]) && isset($_COOKIE[$this->remHashName])) {
            $userId_md5 = DB::escape($_COOKIE[$this->remIdName]);
            $hash = DB::escape($_COOKIE[$this->remHashName]);
            $q = "select user_id `id` from `user` where md5(`user_id`) = '$userId_md5' and $this->dbHashName = '$hash'";
            if ($userId = DB::result($q, "id")) {
                $_SESSION[$this->idName] = $userId;
                $_SESSION[$this->hashName] = $hash;
                setcookie($this->remIdName, $_COOKIE[$this->remIdName], time() + 60 * 60 * 24 * 3, "/");
                setcookie($this->remHashName, $_COOKIE[$this->remHashName], time() + 60 * 60 * 24 * 3, "/");
            }
        }

        // Authorization for API, for authorize method
        if (!empty($GLOBALS[self::class]['login']) && !empty($GLOBALS[self::class]['password'])) {
            $q = "
                    SELECT user_id, role_id, login, password, $this->dbHashName, r.name role_name
                    FROM user u
                    JOIN user_role r USING(role_id)
                    WHERE login='" . DB::escape($GLOBALS[self::class]['login']) . "' AND u.active=1 AND r.active=1
                ";
            $r = DB::query($q);
            if ($row = DB::fetch($r)) {
                if (md5($GLOBALS[self::class]['password']) === $row['password']) {
                    $this->id = (int)$row[$this->idName];
                    $this->login = $row['login'];
                    $this->role_id = (int)$row['role_id'];
                    $this->role_name = $row['role_name'];
                }
            }
        }

        if (!empty($_SESSION[$this->idName]) && !empty($_SESSION[$this->hashName])) {
            $q = "
                    SELECT user_id, role_id, login, $this->dbHashName, r.name role_name
                    FROM user u
                    JOIN user_role r USING(role_id)
                    WHERE user_id=" . (int)$_SESSION[$this->idName] . " AND u.active=1 AND r.active=1
                ";
            $r = DB::query($q);
            if ($row = DB::fetch($r)) {
                if ($_SESSION[$this->hashName] === $row[$this->dbHashName]) {
                    $this->id = (int)$_SESSION[$this->idName];
                    $this->login = $row['login'];
                    $this->role_id = (int)$row['role_id'];
                    $this->role_name = $row['role_name'];
                }
            }
        }

        if ($this->id) {
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
            while ($row = DB::fetch($r)) {
                $this->priv_ids[(int)$row['priv_id']] = (int)$row['priv_id'];
                $this->priv_names[$row['name']] = $row['name'];
            }
        }
    }

    /**
     * Возвращает информацию о пользователе
     * @param string|false $field (optional) - если указано, возвращает конкретное поле
     * @param bool $buffered
     * @return false|array|string
     */
    public function info($field = false, $buffered = true)
    {
        if (!$this->id) {
            return false;
        }
        if (!isset($this->info) || !$buffered) {
            $q = "select * from user where user_id = " . $this->id;
            $this->info = DB::result($q);
            $this->info['role_name'] = $this->role_name;
        }
        $ret = false;
        if ($field === false) {
            $ret = $this->info;
        } elseif (isset($this->info[$field])) {
            $ret = $this->info[$field];
        }
        return $ret;
    }

    public function privIds()
    {
        return count($this->priv_ids) ? $this->priv_ids : array(0);
    }

    public function privNames()
    {
        return count($this->priv_names) ? $this->priv_names : array('');
    }

    public function ican($priv)
    {
        if (false === $priv) {
            return $this->priv_names;
        }
        if (is_int($priv)) {
            return isset($this->priv_ids[$priv]);
        }
        return isset($this->priv_names[$priv]);
    }

}
