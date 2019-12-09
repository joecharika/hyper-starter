<?php


namespace Hyper\Twig;


use Hyper\Application\HyperApp;
use Hyper\Files\Folder;
use Hyper\Files\ImageHandler;
use Hyper\Functions\Logger;
use Hyper\Functions\Str;
use Twig\Environment;
use Twig\TwigFunction;

abstract class TwigFunctions
{
    public static function attach(Environment &$twig)
    {
        foreach (['img', 'optImg', 'base64', 'css', 'js', 'asset', 'get'] as $fn)
            $twig->addFunction(new TwigFunction($fn, "Hyper\\Twig\\TwigFunctions::$fn"));
    }

    #region Functions
    static function img($image, $path = 'img')
    {
        return "/assets/$path/$image";
    }

    static function optImg($image, $size = 10, $path = 'img')
    {
        if (!isset($image)) return null;
        if (Str::contains($image, 'assets'))
            return "/assets/$path/$image";

        return ImageHandler::getImage($image, $size);
    }

    static function base64($image, $path = 'img')
    {
        $file = Folder::assets() . "$path/$image";
        $var = base64_encode(file_get_contents($file));
        return "data:;base64,$var";
    }

    static function css($stylesheet, $ext = 'css')
    {
        return self::getAsset($stylesheet, $ext);
    }

    static function js($script, $ext = 'js')
    {
        return self::getAsset($script, $ext);
    }

    static function asset($asset)
    {
        return '/assets/' . $asset;
    }

    static function get($parent, $key, $match = 'id')
    {
        foreach ($parent as $object) {
            if ($object->$match === $key)
                return $object;
        };

        return null;
    }

    #endregion

    #region Utils

    private static function getAsset(string $_, $_t): string
    {
        $file = "{$_t}/{$_}" . ($_t ?? HyperApp::$debug ? ".$_t" : ".min.$_t");

        if (file_exists(Folder::assets() . $file)) {
            return "/assets/$file";
        }

        $file = "{$_t}/{$_}";

        if (Folder::assets() . "$file.$_t")
            $file = "$file.$_t";
        elseif (Folder::assets() . "$file.min.$_t")
            $file = "$file.min.$_t";
        else Logger::log("$_ file was not found.", Logger::ERROR);

        return "/assets/$file";
    }
    #endregion
}