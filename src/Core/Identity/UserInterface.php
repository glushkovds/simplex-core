<?php

namespace Simplex\Core\Identity;

use Simplex\Core\Identity\Models\User;

interface UserInterface
{

    /**
     * @return int|null
     */
    public static function id(): ?int;

    /**
     * @return User|null
     */
    public static function model(): ?User;

    /**
     * @param User $user
     * @return void
     */
    public static function init(User $user);

    /**
     * Forget identity. Methods id and model will return null.
     * @return void
     */
    public static function forget();

    /**
     * @param int|string $priv User privilege id or name
     * @return bool
     */
    public static function ican($priv);

}