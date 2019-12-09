<?php


namespace Hyper\Utils;


class Generator
{
    /**
     * Generate new token
     * @param string $start Starting key
     * @return string
     */
    public static function token($start = '__')
    {
        return $start . uniqid() . uniqid() . uniqid() . uniqid() . date('jNWto.his');
    }

    public static function forgeUrl(string $string)
    {
        return strtr(strtolower($string), [' ' => '-']);
    }
}