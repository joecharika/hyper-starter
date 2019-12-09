<?php
/**
 * hyper v1.0.0-beta.2 (https://hyper.com/php)
 * Copyright (c) 2019. J.Charika
 * Licensed under MIT (https://github.com/joecharika/hyper/master/LICENSE)
 */

namespace Hyper\Http;


/**
 * Class HttpMessage
 * @package Hyper\Notifications
 */
class HttpMessage
{
    /**
     * @var string $message
     */
    public $message;

    /**
     * @var string $type
     */
    public $type;

    /**
     * HttpMessage constructor.
     * @param string|null $message
     * @param string $type
     */
    public function __construct($message, $type = HttpMessageType::INFO)
    {
        $this->message = $message;
        $this->type = $type;
    }

    public function __toString()
    {
        return "message=$this->message&messageType=$this->type";
    }
}