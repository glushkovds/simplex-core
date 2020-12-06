<?php

namespace Simplex\Core;


class DB
{

    /**
     *
     * @var MySQL
     */
    protected static $db = false;
    protected static $queries = array();

    private function __construct()
    {

    }

    private static function create()
    {
        switch (Container::getConfig()::$db_type) {
            case 'mysql' :
                return new DB\MySQL();
            default :
                die("<b>Error! Unknown Database type.</b>");
        }
    }

    public static function connect()
    {
        static::$db = static::create();
        static::$db->connect();
    }

    public static function bind($vars)
    {
        if (is_array($vars)) {
            return static::$db->bind($vars);
        }
        return false;
    }

    public static function &query($q)
    {
        $t = microtime(1);
        $r = static::$db->query($q);
        if (static::$db->errno()) {
            static::logError($q);
        }
        if (imDev()) {
            static::$queries[] = array('time' => microtime(1) - $t, 'sql' => $q, 'error' => static::$db->errno() ? static::$db->error() : '');
        }
        return $r;
    }

    protected static function logError($query)
    {
        if (empty(Container::getConfig()::$db_errorLog)) {
            return;
        }
        $errno = static::$db->errno();
        $error = static::$db->error();
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $call = @$trace[1]['file'] . ':' . @$trace[1]['line'];
        $message = "MySQL error $errno: $error; sql: $query; call: $call";
        error_log($message);
        if (imDev()) {
            echo $message . "\n";
        }
    }

    public static function fetch(&$result)
    {
        return static::$db->fetch($result);
    }

    public static function result($q, $field = '')
    {
        return static::$db->result(static::query($q), $field);
    }

    public static function assoc($q, $field1 = false, $field2 = false)
    {
        return static::$db->assoc(static::query($q), $field1, $field2, $q);
    }

    public static function insertID()
    {
        return static::$db->insertID();
    }

    public static function getTime($time, $length = 4)
    {
        $a = explode(' ', $time);
        $b = explode(' ', microtime());
        return substr($b[0] - $a[0] + $b[1] - $a[1], 0, $length + 2);
    }

    public static function debug($time, $length = 4)
    {
        if (User::ican('debug')) {
            $time = static::getTime($time, $length);
            echo '<div style="position:absolute;z-index:10000;top:18px;right:50%;margin-right:-600px;cursor:pointer;border:1px dashed #999;padding:2px 7px;line-height:1.2;background-color:#EEE;font-size:11px;color:#363"  onclick="document.getElementById(\'debug-box\').style.display = document.getElementById(\'debug-box\').style.display==\'block\' ? \'none\' : \'block\'"><span style="color:#444">', count(static::$queries), '</span> / <span style="color:#666">', number_format($time, $length), '</span></div>';
            echo '<div id="debug-box" style="display:none;position:absolute;z-index:10000;top:48px;right:50%;margin-right:-600px;width:300px;height:500px;overflow:auto;border:1px dashed #999;padding:2px 7px;line-height:1.2;background-color:#EEE;font-size:11px;color:#363">';
            echo '<table style="table-layout:auto;">';
            $sumTime = 0;
            foreach (static::$queries as $key => $val) {
                $sumTime += $val['time'];
                echo '<tr>';
                echo '<td style="color:#999;padding:2px 4px;vertical-align:top">', $key + 1, '</td>';
                echo '<td style="color:#999;padding:2px 4px;vertical-align:top">', number_format($val['time'], $length), '</td>';
                echo '<td style="white-space:nowrap;color:#666;padding:2px 4px">', nl2br(trim($val['sql'])), '<br /><b>', nl2br($val['error']), '</b></td>';
                echo '</tr>';
            }
            echo '<tr>';
            echo '<td style="color:#999;padding:2px 4px;vertical-align:top">!</td>';
            echo '<td style="color:#999;padding:2px 4px;vertical-align:top">', number_format($sumTime, $length), '</td>';
            echo '<td style="white-space:nowrap;color:#666;padding:2px 4px">Общее время на запросы</td>';
            echo '</tr>';
            echo '</table>';
            echo '</div>';

            if (Core::uri(0) != 'admin') {
                echo '<div style="position:absolute;z-index:10000;top:48px;right:50%;margin-right:-600px;padding:1px 8px;background:#EEE;border:1px dashed #666;font-size:11px;color:#666">', number_format(memory_get_peak_usage() / 1024, 1, ',', ' '), ' - ', number_format((memory_get_usage() - $GLOBALS['m0']) / 1024, 1, ',', ' '), '</div>';
            }
        }
    }

    public static function errno()
    {
        return static::$db->errno();
    }

    public static function error()
    {
        return static::$db->error();
    }

    public static function escape($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $index => $str) {
                $mixed[$index] = static::escape($str);
            }
            return $mixed;
        }

        return static::$db->escape($mixed);
    }

    public static function enumValues($table, $field)
    {
        $buffer = &$_ENV['enum_values'][$table][$field];

        if (!isset($buffer)) {
            $q = "SHOW FULL COLUMNS FROM `$table` LIKE '$field'";
            $row = DB::result($q);
            $enumArray = array();
            preg_match_all("/'(.*?)'/", $row['Type'], $enumArray);
            $enumFields = $enumArray[1];
            $names = explode(';;', $row['Comment']);
            if (count($names) == count($enumFields)) {
                $ret = array();
                foreach ($names as $index => $name) {
                    $ret[$enumFields[$index]] = trim($name);
                }
                $buffer = $ret;
            } else {
                $buffer = array();
                foreach ($enumFields as $name) {
                    $buffer[$name] = $name;
                }
            }
        }

        return $buffer;
    }

    public static function columnInfo($table, $field)
    {
        $q = "SHOW FULL COLUMNS FROM `$table` LIKE '$field'";
        $row = DB::result($q);
        return $row;
    }

    public static function affectedRows()
    {
        return static::$db->affectedRows();
    }

    public static function transactionStart()
    {
        static::query('BEGIN');
    }

    public static function transactionCommit()
    {
        static::query('COMMIT');
    }

    public static function transactionRollback()
    {
        static::query('ROLLBACK');
    }

    /**
     *
     * @param bool $success true - commit, false - rollback
     */
    public static function transactionEnd($success)
    {
        $success ? static::transactionCommit() : static::transactionRollback();
    }

    public static function seek(&$r, $index)
    {
        return static::$db->seek($r, $index);
    }

    public static function fetchReset(&$r)
    {
        return static::seek($r, 0);
    }

}
