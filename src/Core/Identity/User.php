<?php


namespace Simplex\Core\Identity;

use \Simplex\Core\Identity\Models\User as UserModel;

class User implements UserInterface
{

    /** @var UserModel|null */
    protected static $model;

    /**
     * @inheritDoc
     */
    public static function id(): ?int
    {
        return static::$model ? static::$model->getId() : null;
    }

    /**
     * @inheritDoc
     */
    public static function model(): ?UserModel
    {
        return static::$model;
    }

    /**
     * @inheritDoc
     */
    public static function init(UserModel $user)
    {
        static::$model = $user;
    }

    /**
     * @inheritDoc
     */
    public static function forget()
    {
        static::$model = null;
    }

    /**
     * @inheritDoc
     */
    public static function ican($priv)
    {
        return static::$model->ican($priv);
    }
}