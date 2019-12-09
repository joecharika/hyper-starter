<?php


namespace Hyper\Files;


use Hyper\Exception\HyperException;
use function file_exists;

/**
 * Class ImageHandler
 * @package Hyper\Files
 */
abstract class ImageHandler
{
    /**
     * @param $image
     * @param array $sizes
     * @return bool|object
     * @throws HyperException
     */
    public static function optimise($image, $sizes = [10, 30, 50, 90, 100])
    {
        $name = FileHandler::getName($image);
        $extension = FileHandler::getExtension($image);
        $org = $image;

        $image = self::image($image);

        $result = true;

        foreach ($sizes as $size) {
            $folder = Folder::assets() . "img/optimised/x{$size}/";
            Folder::create($folder);

            $result &= self::save($org, $image, "$folder$name.$extension", $size);

            if (!$result)
                throw new HyperException("Failed to save $folder/$name.$extension");
        }

        return !$result ?: self::getOptimisedImg("$name.$extension");
    }

    /**
     * @param $fileName
     * @return false|resource
     * @throws HyperException
     */
    public static function image($fileName)
    {
        $type = @getimagesize($fileName)['mime'];

        if ($type == 'image/jpeg')
            return imagecreatefromjpeg($fileName);
        elseif ($type == 'image/gif')
            return imagecreatefromgif($fileName);
        elseif ($type == 'image/png')
            return imagecreatefrompng($fileName);
        else
            throw new HyperException('Unknown image type');
    }

    /**
     * @param $originalFile
     * @param $imageResource
     * @param $savePath
     * @param $quality
     * @return bool
     */
    private static function save($originalFile, $imageResource, $savePath, $quality)
    {
        $info = getimagesize($originalFile);
        $type = @$info['mime'];

        if ($type == 'image/png')
            return imagepng(
                self::resize($imageResource, $info, $quality / 100),
                $savePath,
                10 - ($quality / 10)
            );
        else
            return imagejpeg(
                self::resize($imageResource, $info, $quality / 100),
                $savePath,
                $quality
            );
    }

    /**
     * @param $source
     * @param $orgSize
     * @param int $percent
     * @return false|resource
     */
    public static function resize($source, $orgSize, $percent = 1)
    {
        list($width, $height) = $orgSize;

        $newWidth = $width * $percent;
        $newHeight = $height * $percent;

        $thumb = imagecreatetruecolor($newWidth, $newHeight);

        imagecopyresized($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return $thumb;
    }

    /**
     * @param $fileName
     * @return object
     */
    public static function getOptimisedImg($fileName)
    {
        return (object)[
            'xs' => self::getImage($fileName, 10) ?? self::getImage($fileName, 20),
            'sm' => self::getImage($fileName, 30) ?? self::getImage($fileName, 40),
            'md' => self::getImage($fileName, 50) ?? self::getImage($fileName, 60),
            'lg' => self::getImage($fileName, 70) ?? self::getImage($fileName, 80),
            'xl' => self::getImage($fileName, 90),
            'org' => self::getImage($fileName, 100),
        ];
    }

    /**
     * @param $fileName
     * @param int $size
     * @return string|null
     */
    public static function getImage($fileName, int $size)
    {
        $base = Folder::assets() . 'img/optimised/';
        $name = FileHandler::getName($fileName);
        $extension = FileHandler::getExtension($fileName);

        if (file_exists("{$base}x{$size}/$name.$extension")) {
            return "/assets/img/optimised/x{$size}/$name.$extension";
        }
        return null;
    }
}