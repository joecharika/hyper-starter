<?php


namespace Hyper\Database;


use Hyper\Application\Request;
use Hyper\Exception\HyperError;
use Hyper\Exception\HyperException;
use Hyper\Functions\Arr;
use Hyper\Functions\Obj;
use Hyper\Functions\Str;
use Hyper\Reflection\Annotation;

class FileHandler
{
    use HyperError;
    private $context;

    public function __construct(DatabaseContext $context)
    {
        $this->context = $context;
    }

    /**
     * @param array $entity
     * @return array
     */
    public function uploads(array $entity): array
    {
        $entityArray = $entity;
        foreach ($entityArray as $item => $value) {
            $isUpload = Annotation::getPropertyAnnotation($this->context->model, $item, 'isFile');
            if ($isUpload) {
                $file = $this->handleUpload(Obj::property(Request::files(), $item));
                if (!!isset($file)) {
                    $fileType = $entityArray[$item]->type;
                    $uploadType = Annotation::getPropertyAnnotation($this->context->model, $item, "UploadAs");
                    if ($uploadType === "Base64") {
                        $var = base64_encode(file_get_contents($file));
                        $entityArray[$item] = "data:$fileType;base64,$var";
                    } else {
                        $entityArray[$item] = "/" . $file;
                    }
                }
            }
        }
        return $entityArray;
    }

    /**
     * @param $file
     * @return string|null
     */
    private function handleUpload($file)
    {
        #If there is no file at all then no upload will take place
        if (!isset($file)) return null;

        #If the file has a name but no temporary name hence the file did not reach the server
        if (!empty($file['name']) && empty($file['tmp_name'])) self::error(new HyperException('This file could not be uploaded'));

        #If the temporary name is empty also the file did not reach the server
        if (empty($file['tmp_name'])) return null;

        #Convert the file to an object
        $file = (object)$file;

        #Get the file type and pluralize it
        $type = Str::pluralize(Arr::key(explode('/', $file->type), 0, ''));
        $targetDir = "assets/uploads/$type";

        #Create folder for specific file type if not exists
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

        $targetDir = "$targetDir/";
        $targetFile = $targetDir . basename($file->name);
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        #Complete the upload by moving the file into the specific type directory
        if (move_uploaded_file($file->tmp_name, $targetFile)) {
            $newFileName = $targetDir . uniqid() . uniqid() . "." . $imageFileType;
            rename($targetFile, $newFileName);
            return $newFileName;
        } else self::error(new HyperException('File upload failed'));

        return null;
    }
}