<?php


namespace Simplex\Core\DB;

use Simplex\Core\Container;

/**
 * Class MySQLi
 * @package Simplex\Core\DB
 * @deprecated use MySQL
 */
class MySQLi implements Adapter
{

    /**
     *
     * @var \mysqli
     */
    protected $link;

    public function connect(): bool
    {
        $config = Container::getConfig();
        $this->link = mysqli_connect($config::$db_host, $config::$db_user, $config::$db_pass) or die("<b>Error! Can not connect to MySQL.</b>");
        mysqli_select_db($this->link, $config::$db_name) or die("<b>Error! Can not select database.</b>");
        mysqli_query($this->link, "SET names utf8");
        mysqli_query($this->link, "SET time_zone = '" . date('P') . "'");
        return true;
    }

    public function bind(array $params)
    {
        $q = 'SET';
        foreach ($params as $key => $val) {
            $q .= ' @' . $key . '=' . (is_numeric($val) ? $val : "'" . mysqli_escape_string($this->link, $val) . "'") . ',';
        }
        return mysqli_query($this->link, substr($q, 0, -1));
    }

    public function query(string $q, array $params = [])
    {
        $_ENV['lastq'] = $q;
        return mysqli_query($this->link, $q);
    }

    public function fetch(&$result)
    {
        return mysqli_fetch_assoc($result);
    }

    public function seek(&$r, int $index): bool
    {
        return @mysqli_data_seek($r, $index);
    }

    public function result($r, $field = '')
    {
        if (!$r) {
            return false;
        }
        if (is_int($field)) {
            $result = mysqli_fetch_row($r);
            return $result[$field] ? $result[$field] : false;
        }
        $result = mysqli_fetch_assoc($r);
        return $field ? (isset($result[$field]) ? $result[$field] : false) : $result;
    }

    public function assoc(&$r, $field1 = false, $field2 = false, $q = FALSE)
    {
        $rows = array();
        if ($field1) {
            if ($field2 === false) {
//                if(!$r){
//                    echo $q;die;
//                }
                while ($row = mysqli_fetch_assoc($r)) {
                    $rows[$row[$field1]] = $row;
                }
            } elseif ($field2) {
                while ($row = mysqli_fetch_assoc($r)) {
                    $rows[$row[$field1]][$row[$field2]] = $row;
                }
            } else {
                while ($row = mysqli_fetch_assoc($r)) {
                    $rows[$row[$field1]][] = $row;
                }
            }
        } else {
            if ($r instanceof \mysqli_result) {
                while ($row = mysqli_fetch_assoc($r)) {
                    $rows[] = $row;
                }
            } else {

            }
        }
        return $rows;
    }

    public function insertID(): string
    {
        $ret = mysqli_fetch_row($this->query("SELECT LAST_INSERT_ID()"));
        return $ret[0];
    }

    public function errno(): string
    {
        return mysqli_errno($this->link);
    }

    public function error(): string
    {
        return mysqli_error($this->link);
    }

    public function errorPrepared(): string
    {
        $errs = array(
            1451 => 'Нельзя удалить запись, имеются связанные записи'
        );
        $n = mysqli_errno($this->link);
        return $n > 0 ? 'Ошибка. Код: ' . $n . '. ' . (isset($errs[$n]) ? $errs[$n] : mysqli_error($this->link)) : '';
    }

    public function escape(string $str): string
    {
        return mysqli_escape_string($this->link, $str);
    }

    public function affectedRows(): int
    {
        return mysqli_affected_rows($this->link);
    }

    /**
     * @inheritDoc
     */
    public function beginTransaction()
    {
        mysqli_query($this->link, 'START TRANSACTION');
    }

    /**
     * @inheritDoc
     */
    public function commitTransaction()
    {
        mysqli_query($this->link, 'COMMIT');
    }

    /**
     * @inheritDoc
     */
    public function rollbackTransaction()
    {
        mysqli_query($this->link, 'ROLLBACK');
    }
}

