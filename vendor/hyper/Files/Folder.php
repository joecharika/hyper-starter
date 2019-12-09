<?php


namespace Hyper\Files;


abstract class Folder
{
    public static function controllers(): string
    {
        return self::root() . 'controllers/';
    }

    public static function root(): string
    {
        return $_SERVER['DOCUMENT_ROOT']  . '/';
    }

    public static function views(): string
    {
        return self::root() . 'views/';
    }

    public static function models(): string
    {
        return self::root() . 'models/';
    }

    public static function assets(): string
    {
        return self::root() . 'assets/';
    }

    public static function helpers(): string
    {
        return self::root() . 'helpers/';
    }

    public static function log(): string
    {
        return self::root() . 'log/';
    }
}