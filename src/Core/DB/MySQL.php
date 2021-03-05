<?php

namespace Simplex\Core\DB;

use PDO;
use PDOException;
use PDOStatement;
use Simplex\Core\Container;

/**
 * Class MySQL
 *
 * @package Simplex\Core\DB
 */
class MySQL implements Adapter
{
    /**
     * @var PDO
     */
    private $db;

    /**
     * @var PDOStatement
     */
    private $lastQuery;

    public function connect(): bool
    {
        $cfg = Container::getConfig();
        $host = $cfg::$db_host;
        $database = $cfg::$db_name;

        try {
            $this->db = new PDO("mysql:host=$host;dbname=$database", $cfg::$db_user, $cfg::$db_pass);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

            return true;
        } catch (PDOException $ex) {
            die('<b>Error! Could not connect to database: ' . $ex->getMessage() . '</b>');
        }
    }

    public function bind(array $vars)
    {
        $sql = [];
        foreach ($vars as $k => $v) {
            $sql[] = "@$k=" . (is_numeric($v) ? $v : "'" . $this->escape($v) . "'");
        }

        $query = $this->db->prepare('SET ' . implode(',', $sql));
        $query->execute();
    }

    /**
     * @param string $q
     *
     * @return false|PDOStatement
     */
    public function query(string $q)
    {
        $_ENV['lastq'] = $q;

        $query = $this->db->prepare($q, [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
        if ($query->execute()) {
            $this->lastQuery = $query;
            return $query;
        } else {
            return false;
        }
    }

    public function fetch(&$result)
    {
        if ($result === false) {
            return null;
        } else {
            /** @var PDOStatement $result */
            return $result->fetch(PDO::FETCH_ASSOC);
        }
    }

    public function seek(&$result, int $index): bool
    {
        if ($result === false) {
            return false;
        } else {
            /** @var PDOStatement $result */
            $result->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_ABS, max($index - 1, 0));
            return true;
        }
    }

    /**
     * @param bool|PDOStatement $result
     * @param int|string $field
     *
     * @return bool|array
     */
    public function result($result, $field = '')
    {
        if ($result === false) {
            return false;
        } else {
            /** @var PDOStatement $result */
            if (is_int($field)) {
                $r = $result->fetch(PDO::FETCH_NUM);
                return $r && isset($r[$field]) ? $r[$field] : false;
            } else {
                $r = $result->fetch(PDO::FETCH_ASSOC);
                return $field ? ($r && isset($r[$field]) ? $r[$field] : false) : $r;
            }
        }
    }

    /**
     * @param bool|PDOStatement $result
     * @param bool $f1
     * @param bool $f2
     * @param bool $q
     *
     * @return array|bool
     */
    public function assoc(&$result, $f1 = false, $f2 = false, $q = false)
    {
        if ($result === false) {
            if ($q) {
                echo $q;
                die;
            } else {
                return false;
            }
        } else {
            $rows = [];

            /** @var PDOStatement $result */
            if ($f1) {
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    if ($f2 === false) {
                        $rows[$row[$f1]] = $row;
                    } elseif ($f2) {
                        $rows[$row[$f1]][$row[$f2]] = $row;
                    } else {
                        $rows[$row[$f1]][] = $row;
                    }
                }
            } else {
                $rows = $result->fetchAll(PDO::FETCH_ASSOC);
            }

            return $rows;
        }
    }

    public function insertId(): string
    {
        return $this->db->lastInsertId();
    }

    public function errno(): ?string
    {
        return $this->db->errorCode();
    }

    public function error(): ?string
    {
        return $this->db->errorInfo()[2];
    }

    public function errorPrepared(): string
    {
        $errorList = [
            1451 => 'Нельзя удалить запись - имеются связанные записи'
        ];

        $errNo = $this->errno();
        if ($errNo > 0) {
            $message = "Ошибка. Код: $errNo. ";
            $message .= isset($errorList[$errNo]) ? $errorList[$errNo] : $this->error();

            return $message;
        } else {
            return '';
        }
    }

    public function escape(string $str): string
    {
        return substr($this->db->quote($str), 1, -1);
    }

    public function affectedRows(): int
    {
        return $this->lastQuery ? $this->lastQuery->rowCount() : 0;
    }
}