<?php

namespace Hyper\Exception;

use Exception;

/**
 * Class HyperException
 * @package hyper\Exception
 */
class HyperException extends Exception
{
    /** @var int */
    public $code = 500;

    /** @var string */
    public $message = 'Internal Server error';

    /**
     * @param mixed $file
     * @return HyperException
     */
    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @param mixed $line
     * @return HyperException
     */
    public function setLine($line)
    {
        $this->line = $line;
        return $this;
    }


}