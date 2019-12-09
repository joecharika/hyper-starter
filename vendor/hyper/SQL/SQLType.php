<?php


namespace Hyper\SQL;


use Hyper\QueryBuilder\Query;

/**
 * Class SQLType
 * @package Hyper\SQL
 */
class SQLType
{
    /**
     * @param int $size
     * @return QueryBuilder
     */
    public static function int($size = 10)
    {
        return self::type('int', $size);
    }

    /**
     * @param $type
     * @param null $size
     * @return Query
     */
    public static function type($type, $size = null)
    {
        if (isset($size)) $type = "$type($size)";
        return new Query(null, null, $type);
    }

    /**
     * @return QueryBuilder
     */
    public static function decimal()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function newDecimal()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function float()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function double()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function bit()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function tiny()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function short()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function long()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function longLong()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function int24()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function enum()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function timestamp()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function date()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function time()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function datetime()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function newDate()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function interval()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function set()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function varString()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function string()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function char()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function geometry()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function blob()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function tinyBlob()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function mediumBlob()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function longBlob()
    {
    }

    /**
     * @return QueryBuilder
     */
    public static function text()
    {
    }

}