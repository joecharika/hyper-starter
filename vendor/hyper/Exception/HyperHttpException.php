<?php


namespace Hyper\Exception;


use Exception;

class HyperHttpException extends HyperException
{
    public static function notFound($message = "Not found"): Exception
    {
        return self::get($message, "404");
    }

    public static function badRequest(): Exception
    {
        return self::get("Bad request", "400.5");
    }

    public static function notAuthorised(): Exception
    {
        return self::get("Not authorised", "403");
    }

    private static function get($message, $code)
    {
        $exc = new HyperHttpException($message, $code);
        $var = @debug_backtrace()[1];

        $exc->line = @$var['line'];
        $exc->file = @$var['file'];

        return $exc;
    }
}

class HttpResponse extends HyperHttpException
{
}