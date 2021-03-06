<?php
/**
 * hyper v1.0.0-beta.2 (https://hyper.com/php)
 * Copyright (c) 2019. J.Charika
 * Licensed under MIT (https://github.com/joecharika/hyper/master/LICENSE)
 */

namespace Hyper\Functions;


use function is_null;
use function lcfirst;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function strtr;
use function substr;
use function ucfirst;
use function ucwords;

/**
 * Class Str alias String
 * @package Hyper\Functions
 */
abstract class Str
{
    /**
     * Pluralize a string
     *
     * @param $singular
     * @param null $plural
     * @return string|null
     */
    public static function pluralize(string $singular, $plural = null): string
    {
        if (is_null($singular)) return null;
        if ($plural !== null) return $plural;

        $last_letter = strtolower($singular[strlen($singular) - 1]);
        switch ($last_letter) {
            case 'rs':
                return $singular;
            case 'in':
            case 'ch':
            case 's':
            case 'sh':
            case 'x':
            case 'z':
                return $singular . 'es';
            case 'f':
                return substr($singular, 0, -1) . 'ves';
            case 'fe':
                return substr($singular, 0, -2) . 'ves';
            case 'of':
            case 'ief':
            case 'ay':
            case 'ey':
            case 'iy':
            case 'oy':
            case 'uy':
            default:
                return $singular . 's';
            case 'y':
                return substr($singular, 0, -1) . 'ies';
        }
    }

    public static function singular($plural)
    {
        if (self::endsWith($plural, 'ies'))
            return substr($plural, 0, -3) . 'y';
        if (self::endsWith($plural, 'es'))
            return substr($plural, 0, -2) . 's';
        if (self::endsWith($plural, 's'))
            return substr($plural, 0, -1);

        return $plural;
    }

    /**
     * Function to check the string is ends with given substring or not
     * @param $string
     * @param $endString
     * @return bool
     */
    public static function endsWith($string, $endString)
    {
        return substr($string, -strlen($endString)) === $endString;
    }

    /**
     * Function to check string starting with given substring
     * @param $string
     * @param $startString
     * @return bool
     */
    public static function startsWith($string, $startString)
    {
        return substr($string, 0, strlen($startString)) === $startString;
    }

    /**
     * Transform Snake case string to Camel case string
     *
     * @param string $input
     * @param string $separator
     * @return string
     */
    public static function toCamel($input, $separator = '_'): string
    {
        return ucfirst(str_replace($separator, '', ucwords($input, $separator)));
    }

    /**
     * Transform Snake case string to Pascal case string
     *
     * @param string $input
     * @param string $separator
     * @return string
     */
    public static function toPascal(string $input, $separator = '_'): string
    {
        return lcfirst(str_replace($separator, '', ucwords($input, $separator)));
    }

    /**
     * Remove substrings(filters) from a string(string)
     *
     * @param string $string
     * @param array $filters
     * @return string
     */
    public static function filter(string $string, array $filters): string
    {
        foreach ($filters as $filter) {
            $string = strtr($string, $filter, '');
        }
        return $string;
    }

    /**
     * Check if string(haystack) contains the substring(needle)
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function contains(string $haystack, string $needle): bool
    {
        return strpos(strtolower($haystack), strtolower($needle)) !== false;
    }

    /**
     * Removes\replaces blank lines from a string
     * @param string $string string to trim blanks
     * @param string $to replacement
     * @return string string without blank\ with replaced blanks
     */
    public static function trimLine(string $string, string $to = ''): string
    {
        $content = explode("\n", $string);
        $result = [];

        foreach ($content as $line) {
            $line = trim($line);
            if (strlen($line) !== 0) {
                array_push($result, $line);
            }
        }
        return implode($to, $result);
    }

}
