<?php

namespace Simplex\Core;


use Simplex\Core\DB\AQ;
use Simplex\Core\DB\Expr;
use Simplex\Core\DB\Where;
use Simplex\Core\Helpers\Str;

/**
 * Usage
 *
 * 1. Add file to your extension. For example: /ext/test/modeltest.class.php
 * 2. class ModelTest extends  BaseModel {
 * protected $table = 'test';
 * protected $primaryKeyName = 'test_id';
 * }
 * 3. $model = new ModelTest(); or $model = new ModelTest(123);
 *    $model['text'] = '234'; or $model->fill(['text' => '234']); or $model->load(123);
 *    $model->save(); or $model->insert(); or $model->update(); or $model->delete(); or $model->update(['text' => '234']);
 *    $model = ModelTest::findOne('text = "234"'); or $models = ModelTest::find(['text = "234"']);
 */
abstract class ModelBase implements \ArrayAccess, \JsonSerializable
{

    const FLAG_IGNORE = 1;

    protected $id;
    protected $data = [];
    protected $offsetGetData = [];
    protected $dataBeforeUpdate = [];
    protected static $table;
    protected static $primaryKeyName;
    protected $lastError = ['code' => 0, 'text' => ''];

    /**
     * @return string
     */
    public static function getTableName()
    {
        return static::$table;
    }

    /**
     * @return string
     */
    public static function getPrimaryKeyName()
    {
        return static::$primaryKeyName;
    }

    public function getId()
    {
        return $this->id;
    }

    public function __construct($id = null)
    {
        if ($id) {
            $this->load($id);
        }
    }

    public function load($id)
    {
        $id = (int)$id;
        $q = "SELECT * FROM " . static::$table . " WHERE " . static::$primaryKeyName . " = $id";
        if ($row = DB::result($q)) {
            $this->fill($row);
            return true;
        }
        return false;
    }

    public function reload()
    {
        return $this->id && $this->load($this->id);
    }

    /**
     * @param string $fieldName
     * @param bool $withBuffer
     * @return array
     */
    public static function enumValues($fieldName, $withBuffer = true)
    {
        $closure = function () use ($fieldName) {
            return DB::enumValues(static::$table, $fieldName);
        };
        if ($withBuffer && class_exists(' Buffer')) {
            return Buffer::getOrSet('enumValues.' . static::$table . ".$fieldName", $closure);
        }
        return $closure();
    }

    /**
     *
     * @param string|array|Where $where
     * @param null $orderBy
     * @param null $limit
     * @param bool|string $assocKey [optional = false] get result array with model id's (or other field) in keys
     * @return  static[]
     * @throws \Exception
     */
    public static function find($where, $orderBy = null, $limit = null, $assocKey = false)
    {
        return static::findAdv()->andWhere($where)->orderBy($orderBy)->limit($limit)->all($assocKey);
    }

    /**
     * @return AQ
     */
    public static function findAdv()
    {
        $query = (new AQ())->from(static::$table)->setModelClass(static::class);
        return $query;
    }

    /**
     *
     * @param string|array|Where $where
     * @return  static|null
     */
    public static function findOne($where, $returnModelIfNotFound = false)
    {
        $row = static::findAdv()->andWhere($where)->limit(1)->fetchOne();
        return $row ?? ($returnModelIfNotFound ? new static : null);
    }

    /**
     * Заполнить модель данными
     * @param array $data
     * @return $this
     */
    public function fill(array $data)
    {
        $this->data = $data + $this->data;
        if (
            ($suggestId = isset($this->data[static::$primaryKeyName]) ? (int)$this->data[static::$primaryKeyName] : null)
            && (empty($this->id) || $suggestId !== $this->id)
        ) {
            $this->id = $suggestId;
        }
        $this->afterFill();
        return $this;
    }

    /**
     * Вызывается после того, как модель заполнена данными
     */
    protected function afterFill()
    {
    }

    /**
     *
     * @param array $data [optional]
     * @return bool
     */
    public function save(array $data = null)
    {
        $result = false;
        if ($this->beforeSave()) {
            if ($this->id) {
                $result = $this->update($data);
            } else {
                $result = $this->insert($data);
            }
        }
        $this->afterSave($result);
        return $result;
    }

    /**
     *
     * @param mixed $value
     * @return string
     */
    public static function prepareValue($value)
    {
        if ($value instanceof Expr) {
            return (string)$value;
        }
        is_array($value) || is_object($value) && $value = print_r($value, true);
        is_numeric($value) && $setValue = $value;
        is_null($value) && $setValue = 'NULL';
        is_string($value) && $setValue = "'" . DB::escape($value) . "'";
        is_bool($value) && $setValue = (int)$value;
        return $setValue;
    }

    /**
     *
     * @param array $data [optional]
     * @return bool
     */
    public function insert(array $data = null, $flags = 0)
    {
        $data && $this->fill($data);
        $result = false;
        if ($this->beforeInsert()) {
            $set = [];
            foreach ($this->data as $key => $value) {
                $set[] = "`$key` = " . self::prepareValue($value);
            }
            $ignore = $flags & static::FLAG_IGNORE ? ' IGNORE' : '';
            $q = "INSERT$ignore INTO " . static::$table . " SET " . implode(', ', $set);
            if ($result = $this->query($q)) {
                $this->id = DB::insertId();
            }
        }
        $this->afterInsert($result);
        return $result;
    }

    /**
     * @param array|null $data
     * @param int $flags
     * @return static|null
     */
    public static function insertStatic(array $data = null, $flags = 0)
    {
        $model = new static;
        if ($model->insert($data, $flags)) {
            $model->reload();
            return $model;
        }
        return null;
    }

    /**
     *
     * @param array $data
     * @return bool
     */
    public function update(array $data = null)
    {
        if (!$this->id) {
            return false;
        }
        $result = false;
        $data && $this->fill($data);
        if ($this->beforeUpdate()) {
            $uData = $this->data;
            unset($uData[static::$primaryKeyName]);
            $set = static::prepareSet($uData);
            $q = "UPDATE " . static::$table . " SET " . implode(', ', $set) . " WHERE `" . static::$primaryKeyName . "` = $this->id";
            $result = $this->query($q);
            if ($result) {
                $this->reload();
            }
        }
        $this->afterUpdate($result);
        return $result;
    }

    /**
     * @param array $data
     * @return array
     */
    protected static function prepareSet($data)
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "`$key` = " . static::prepareValue($value);
        }
        return $set;
    }

    /**
     *
     * @return boolean
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }
        $result = false;
        $oldData = $this->toArray();
        if ($this->beforeDelete()) {
            $q = "DELETE FROM " . static::$table . " WHERE `" . static::$primaryKeyName . "` = $this->id";
            $result = $this->query($q);
            $this->fill([]);
        }
        $this->afterDelete($result, $oldData);
        return $result;
    }

    public static function bulkDelete($where, $viaModels = false)
    {
        if ($viaModels) {
            $success = true;
            foreach (static::find($where) as $model) {
                $success &= $model->delete();
            }
            return $success;
        } else {
            $whereObj = new Where($where);
            $q = "DELETE FROM " . static::$table . " $whereObj";
            return DB::query($q);
        }
    }

    /**
     * @param array $set
     * @param string|array|Where $where
     * @param bool $viaModels
     * @return bool
     */
    public static function bulkUpdate(array $set, $where, $viaModels = false)
    {
        if ($viaModels) {
            $success = true;
            foreach (static::find($where) as $model) {
                $success &= $model->update($set);
            }
            return $success;
        } else {
            $set = static::prepareSet($set);
            $whereObj = new Where($where);
            $q = "UPDATE " . static::$table . " SET " . implode(', ', $set) . " $whereObj";
            return DB::query($q);
        }
    }

    /**
     * @return bool
     */
    public static function truncate()
    {
        $q = "TRUNCATE TABLE " . static::$table;
        return (bool)DB::query($q);
    }

    /**
     *
     * @param string $q
     * @return boolean
     */
    protected function query($q)
    {
        $success = DB::query($q);
        if (!$success) {
            $this->lastError['code'] = DB::errno();
            $this->lastError['text'] = DB::error();
        }
        return $success;
    }

    protected function setError($text, $code = null)
    {
        if (is_array($text)) {
            $code = (int)@$text['code'];
            $text = (string)@$text['text'];
        }
        $this->lastError['code'] = $code;
        $this->lastError['text'] = $text;
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    protected function beforeInsert()
    {
        return true;
    }

    protected function afterInsert($success)
    {

    }

    protected function beforeUpdate()
    {
        $this->dataBeforeUpdate = $this->data;
        return true;
    }

    protected function afterUpdate($success)
    {

    }

    protected function beforeDelete()
    {
        return true;
    }

    protected function afterDelete($success, array $oldData)
    {

    }

    protected function beforeSave()
    {
        return true;
    }

    protected function afterSave($success)
    {

    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
            if ($offset == static::$primaryKeyName) {
                $this->id = (int)$value;
            }
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]) || isset($this->offsetGetData[$offset]);
    }

    public function offsetUnset($offset)
    {
        if (isset($this->offsetGetData[$offset])) {
            unset($this->offsetGetData[$offset]);
        }
        if (isset($this->data[$offset])) {
            unset($this->data[$offset]);
        }
        if ($offset == static::$primaryKeyName) {
            $this->id = null;
        }
    }

    public function offsetGet($offset)
    {
        if ($this->id && !isset($this->data[$offset])) {
            if ($maybe = ($this->data[Str::toUnderscore($offset)] ?? null)) {
                return $maybe;
            }
            if (method_exists($this, $method = 'offsetGet' . lcfirst($offset))) {
                $this->offsetGetData[$offset] = $this->$method();
            }
            if (method_exists($this, $method = 'offsetGet' . Str::toCamel($offset, false))) {
                $this->offsetGetData[$offset] = $this->$method();
            }
        }
        return isset($this->offsetGetData[$offset])
            ? $this->offsetGetData[$offset]
            : (isset($this->data[$offset]) ? $this->data[$offset] : null);
    }

    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

    public function __unset($name)
    {
        $this->offsetUnset($name);
    }

    public function toArray()
    {
        return $this->data;
    }

    /** @inheritDoc */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @return array
     * @see Core/DB/HowTo/UsingModifiers.md
     */
    public static function aqModifiersDefault(): array
    {
        return [];
    }

}
