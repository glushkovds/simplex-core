<?php

namespace Simplex\Core;

use Simplex\Core\DB\Adapter;

/**
 * Class DB
 * @package Simplex\Core
 * @see Adapter
 */
class DB
{
    /**
     * @var Adapter
     */
    protected static $db;
    protected static $queries = [];

    /**
     * @return Adapter
     */
    protected static function db()
    {
        if (!isset(static::$db)) {
            static::connect();
        }
        return static::$db;
    }

    /**
     * Creates new adapter instance
     *
     * @return Adapter
     */
    private static function create(): Adapter
    {
        /** @noinspection PhpUndefinedVariableInspection */
        switch (Container::getConfig()::$db_type) {
            case 'mysql':
                return new DB\MySQL();
            case 'mysqli':
                return new DB\MySQLi();
            default:
                die("<b>Error! Unknown Database type.</b>");
        }
    }

    /**
     * @return bool
     * @see Adapter::connect()
     */
    public static function connect(): bool
    {
        static::$db = static::create();
        return static::$db->connect();
    }

    /**
     * @param $params
     * @return bool
     * @see Adapter::bind()
     * @deprecated Use $params in query()/assoc()/result() instead
     */
    public static function bind($params): bool
    {
        if (is_array($params)) {
            static::db()->bind($params);
            return true;
        }

        return false;
    }

    /**
     * @param string $q
     * @param array $params
     * @return mixed
     * @see Adapter::query()
     */
    public static function &query(string $q, array $params = [])
    {
        $execTime = microtime(1);

        $result = static::db()->query($q, $params);
        if (static::db()->errno()) {
            static::logError($q);
        }

        if (imDev()) {
            static::$queries[] = [
                'time' => microtime(1) - $execTime,
                'sql' => $q,
                'error' => static::db()->errno() ? static::db()->error() : ''
            ];
        }

        return $result;
    }

    /**
     * Adds error to log
     *
     * @param string $query SQL query
     */
    protected static function logError(string $query)
    {
        /** @noinspection PhpUndefinedVariableInspection */
        if (empty(Container::getConfig()::$db_errorLog)) {
            return;
        }

        $errno = static::db()->errno();
        $error = static::db()->error();

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $call = @$trace[1]['file'] . ':' . @$trace[1]['line'];

        $message = "MySQL error $errno: $error; sql: $query; call: $call";
        error_log($message);

        if (imDev()) {
            echo $message . "\n";
        }
    }

    /**
     * @param $result
     * @return mixed
     * @see Adapter::fetch()
     */
    public static function fetch(&$result)
    {
        return static::db()->fetch($result);
    }

    /**
     * @param string $q SQL query to execute
     * @param string $field
     * @param array $params
     * @return mixed
     * @see Adapter::result()
     */
    public static function result(string $q, $field = '', array $params = [])
    {
        return static::db()->result(static::query($q, $params), $field);
    }

    /**
     * @param string $q SQL query to execute
     * @param mixed $field1
     * @param mixed $field2
     * @param array $params
     * @return mixed
     * @see Adapter::assoc()
     */
    public static function assoc(string $q, $field1 = false, $field2 = false, array $params = [])
    {
        return static::db()->assoc(static::query($q, $params), $field1, $field2);
    }

    /**
     * @return string
     * @see Adapter::insertId()
     */
    public static function insertId(): string
    {
        return static::db()->insertId();
    }

    /**
     * Returns time delta
     *
     * @param string $time Time point
     * @param int $length String limit
     * @return false|string
     */
    public static function getTime(string $time, int $length = 4)
    {
        $a = explode(' ', $time);
        $b = explode(' ', microtime());
        return substr($b[0] - $a[0] + $b[1] - $a[1], 0, $length + 2);
    }

    /**
     * Prints out debug information
     *
     * @param string $time Time point
     * @param int $length String limit
     */
    public static function debug(string $time, $length = 4)
    {
        if (User::ican('debug')) {
            $time = static::getTime($time, $length);

            $divStyle = 'position:absolute;z-index:10000;top:18px;right:50%;margin-right:-600px;cursor:pointer;'
                . 'border:1px dashed #999;padding:2px 7px;line-height:1.2;background-color:#EEE;font-size:11px;'
                . 'color:#363';
            $divOnClick = "document.getElementById('debug-box').style.display="
                . "document.getElementById('debug-box').style.display=='block'?'none':'block'";

            echo '<div style="' . $divStyle . '" onclick="' . $divOnClick . '"><span style="color:#444">';

            // query count
            echo count(static::$queries);
            echo '</span> / <span style="color:#666">';

            // time
            echo number_format($time, $length);
            echo '</span></div>';

            $divStyle = 'display:none;position:absolute;z-index:10000;top:48px;right:50%;margin-right:-600px;"
                . "width:300px;height:500px;overflow:auto;border:1px dashed #999;padding:2px 7px;line-height:1.2;"
                . "background-color:#EEE;font-size:11px;color:#363';

            echo '<div id="debug-box" style="' . $divStyle . '">';
            echo '<table style="table-layout:auto;">';

            $sumTime = 0;
            foreach (static::$queries as $key => $val) {
                $sumTime += $val['time'];

                echo '<tr>';
                echo '<td style="color:#999;padding:2px 4px;vertical-align:top">';

                // query number
                echo $key + 1;
                echo '</td>';
                echo '<td style="color:#999;padding:2px 4px;vertical-align:top">';

                // query execution time
                echo number_format($val['time'], $length);
                echo '</td>';
                echo '<td style="white-space:nowrap;color:#666;padding:2px 4px">';

                // query
                echo nl2br(trim($val['sql']));
                echo '<br /><b>';

                // error
                echo nl2br($val['error']);
                echo '</b></td>';
                echo '</tr>';
            }

            echo '<tr>';
            echo '<td style="color:#999;padding:2px 4px;vertical-align:top">!</td>';
            echo '<td style="color:#999;padding:2px 4px;vertical-align:top">';

            // total execution time
            echo number_format($sumTime, $length);
            echo '</td>';
            echo '<td style="white-space:nowrap;color:#666;padding:2px 4px">Общее время на запросы</td>';
            echo '</tr>';
            echo '</table>';
            echo '</div>';

            if (SF_LOCATION == SF_LOCATION_ADMIN) {
                $divStyle = 'position:absolute;z-index:10000;top:48px;right:50%;margin-right:-600px;padding:1px 8px;'
                    . 'background:#EEE;border:1px dashed #666;font-size:11px;color:#666';

                echo '<div style="' . $divStyle . '">';

                // peak usage in kb
                echo number_format(memory_get_peak_usage() / 1024, 1, ',', ' ');
                echo ' - ';

                // usage in kb
                echo number_format((memory_get_usage() - $GLOBALS['m0']) / 1024, 1, ',', ' ');
                echo '</div>';
            }
        }
    }

    /**
     * @return int
     * @see Adapter::errno()
     */
    public static function errno(): int
    {
        return static::db()->errno();
    }

    /**
     * @return string|null
     * @see Adapter::error()
     */
    public static function error(): ?string
    {
        return static::db()->error();
    }

    /**
     * @return string|null
     * @see Adapter::errorCode()
     */
    public static function errorCode(): ?string
    {
        return static::db()->errorCode();
    }

    /**
     * Escapes array or string
     *
     * @param array|string $mixed Target to escape
     * @return array|string
     * @see Adapter::escape()
     */
    public static function escape($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $index => $str) {
                $mixed[$index] = static::escape($str);
            }

            return $mixed;
        }

        return static::db()->escape((string)$mixed);
    }

    /**
     * Enumerates possible values for column
     *
     * @param string $table Table name
     * @param string $field Column name
     * @return array All possible values
     */
    public static function enumValues(string $table, string $field): array
    {
        $buffer = &$_ENV['enum_values'][$table][$field];

        if (!isset($buffer)) {
            $row = DB::result("SHOW FULL COLUMNS FROM `$table` LIKE '$field'");
            $names = explode(';;', $row['Comment']);

            $enumArray = [];
            preg_match_all("/'(.*?)'/", $row['Type'], $enumArray);

            $enumFields = $enumArray[1];
            if (count($names) == count($enumFields)) {
                $ret = [];
                foreach ($names as $index => $name) {
                    $ret[$enumFields[$index]] = trim($name);
                }

                $buffer = $ret;
            } else {
                $buffer = [];
                foreach ($enumFields as $name) {
                    $buffer[$name] = $name;
                }
            }
        }

        return $buffer;
    }

    /**
     * Returns column information
     *
     * @param string $table Table name
     * @param string $field Column name
     * @return mixed
     */
    public static function columnInfo(string $table, string $field)
    {
        return DB::result("SHOW FULL COLUMNS FROM `$table` LIKE '$field'");
    }

    /**
     * @return int
     * @see Adapter::affectedRows()
     */
    public static function affectedRows(): int
    {
        return static::db()->affectedRows();
    }

    /**
     * Starts DB transaction
     */
    public static function transactionStart()
    {
        static::db()->beginTransaction();
    }

    /**
     * Commits DB transaction
     */
    public static function transactionCommit()
    {
        static::db()->commitTransaction();
    }

    /**
     * Rolls back DB transaction
     */
    public static function transactionRollback()
    {
        static::db()->rollbackTransaction();
    }

    /**
     * Ends transaction
     *
     * @param bool $success True to commit, false to rollback
     */
    public static function transactionEnd(bool $success)
    {
        $success ? static::transactionCommit() : static::transactionRollback();
    }

    /**
     * @param mixed $r Adapter-specific result
     * @param int $index Index
     * @return bool
     * @see Adapter::seek()
     */
    public static function seek(&$r, int $index): bool
    {
        return static::db()->seek($r, $index);
    }

    /**
     * Resets result to position 0
     *
     * @param mixed $r Adapter-specific result
     * @return bool
     */
    public static function fetchReset(&$r): bool
    {
        return static::seek($r, 0);
    }

    public static function wrapString(string $s): string
    {
        return '\'' . self::escape($s) . '\'';
    }

    public static function wrapName(string $s): string
    {
        return '`' . $s . '`';
    }
}
