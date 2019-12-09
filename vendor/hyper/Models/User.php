<?php
/**
 * hyper v1.0.0-beta.2 (https://hyper.com/php)
 * Copyright (c) 2019. J.Charika
 * Licensed under MIT (https://github.com/joecharika/hyper/master/LICENSE)
 */

namespace Hyper\Models;


use Hyper\Application\Authorization;
use Hyper\Database\DatabaseContext;
use Hyper\Functions\Obj;
use Hyper\Http\Cookie;
use Hyper\SQL\SQLType;
use function is_null;

/**
 * Class User
 * @package hyper\Models
 */
class User
{

    /**
     * @var string $id
     * @SQLType varchar(128)
     * @SQLAttributes primary key unique not null
     */
    public $id;

    /**
     * @var string $username
     * @SQLType varchar(128)
     * @SQLAttributes unique not null
     */
    public $username;

    public $name, $lastName, $otherNames,
        $phone, $email, $notes,
        $role, $key, $salt, $lockedOut = false,
        $isEmailVerified, $isPhoneVerified;

    /**
     * @var string
     * @isFile
     * @UploadAs File
     * @required
     */
    public $image;


    public function __construct($username = null)
    {
        if (isset($username))
            $this->username = $username;
    }

    public static function isAuthenticated(): bool
    {
        $token = (new Cookie())->getCookie('__user');

        return empty($token)
            ? false
            : !is_null(
                (new DatabaseContext('claim'))
                    ->first('token', $token)
            );
    }

    public static function isInRole($role)
    {
        return strpos($role, Obj::property((new Authorization)->getSession()->user, 'role')) !== false;
    }

    public static function getName()
    {
        return Obj::property((new Authorization)->getSession()->user, 'name');
    }

    public static function getRole()
    {
        return Obj::property((new Authorization)->getSession()->user, 'role', 'visitor');
    }

    public static function getId()
    {
        return Obj::property((new Authorization)->getSession()->user, 'id');
    }

    public function __toString()
    {
        return "$this->name $this->otherNames $this->lastName";
    }
}