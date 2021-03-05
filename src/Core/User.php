<?php

namespace Simplex\Core;


use Simplex\Core\Helpers\Phone;

class User
{
    public static $id = 0;
    public static $login = '';
    public static $role_id = 0;
    public static $role_name = '';

    /**
     *
     * @var UserInstance
     */
    protected static $instance;

    public static function login($type = 'site')
    {

        if ('site' == $type) {
            self::$instance = new UserInstance('user_id', 'user_hash', 'hash', 'ch', 'cs');
        }
        if ('admin' == $type) {
            self::$instance = new UserInstance('admin_user_id', 'admin_user_hash', 'hash_admin', 'cha', 'csa');
        }

        self::$id = self::$instance->id;
        self::$login = self::$instance->login;
        self::$role_id = self::$instance->role_id;
        self::$role_name = self::$instance->role_name;
    }

    public static function logout()
    {
        self::$instance->logout();
        self::$id = 0;
        self::$login = null;
        self::$role_id = null;
        self::$role_name = null;
    }

    public static function privIds()
    {
        return self::$instance->privIds();
    }

    public static function privNames()
    {
        return self::$instance->privNames();
    }

    /**
     *
     * @param string|int|bool $priv - можно указать название привилегии, можно ее ID. Если false, то вернет все привилегии
     * @return bool|array
     */
    public static function ican($priv = false)
    {
        return self::$instance->ican($priv);
    }

    /**
     * Возвращает информацию о пользователе
     * @param string (optional) $field - если указано, возвращает конкретное поле
     * @return false|array|string
     */
    public static function info($field = false)
    {
        return self::$instance->info($field);
    }

    public static function create($data)
    {
        if (self::$id) {
            return array('success' => false, 'error_code' => 1, 'error' => 'Пользователь уже авторизован');
        }

        $login = Phone::extract((string)@$data['login']);
        $pass = (string)@$data['pass'] ?: rand(100000, 999999);
        $email = (string)@$data['email'];
        $name = (string)@$data['name'];

        $errors = array();
        $login ? null : $errors[] = 'логин';
        $email ? null : $errors[] = 'email';
        $name ? null : $errors[] = 'имя';
        if (count($errors)) {
            return array('success' => false, 'error_code' => 2, 'error' => 'Не указано: ' . implode(', ', $errors));
        }

        $q = "SELECT user_id FROM user WHERE login = '$login'";
        $user = DB::result($q, 0);
        if ($user) {
            return array('success' => false, 'error_code' => 3, 'error' => 'Пользователь с таким логином уже зарегистрирован');
        }

        $q = "SELECT user_id FROM user WHERE email = '$email'";
        $user = DB::result($q, 0);
        if ($user) {
            return array('success' => false, 'error_code' => 4, 'error' => 'Пользователь с таким почтовым адресом уже зарегистрирован');
        }

        $passMD5 = md5($pass);
        $hash = md5(microtime());
        $set = array("login = '$login', email = '$email', name = '$name', password = '$passMD5', hash = '$hash'");
        $q = "INSERT INTO user SET " . implode(', ', $set);
        DB::query($q);
        $userId = DB::insertId();

        if (!$userId) {
            return array('success' => false, 'error_code' => 5, 'error' => 'Ошибка регистрации');
        }

        $HTTPHost = $_SERVER['HTTP_HOST'];
        if (class_exists('PlugPunyCode')) {
            $HTTPHost = PlugPunyCode::httpHost();
        }

        // Отправляем письмо об успешной регистрации
        if ($email) {
            ob_start();
            include $_SERVER['DOCUMENT_ROOT'] . '/theme/default/mail/user_create.tpl';
            $html = ob_get_contents();
            ob_end_clean();
            Mail::staticSend($email, "Регистрация на сайте $HTTPHost", $html);
        }

        // Отправляем СМС с рег. данными
        $smsText = "Для Вас зарегистрирован аккаунт на сайте $HTTPHost: логин $login, пароль $pass";
        Sms::send($login, $smsText);

        if ($userId) {
            setcookie("user_id", $userId, time() + 3600 * 24 * 365, '/');
            setcookie("user_hash", $hash, time() + 3600 * 24 * 365, '/');
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_hash'] = $hash;
            self::login();
        }

        return array('success' => true, 'user_id' => $userId);
    }

    public static function authorizeOnce($login, $password)
    {
        $GLOBALS[self::class]['login'] = $login;
        $GLOBALS[self::class]['password'] = $password;
        static::login();
    }

}

