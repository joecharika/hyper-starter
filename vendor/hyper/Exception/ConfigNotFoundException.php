<?php
/**
 * hyper v1.0.0-beta.2 (https://hyper.com/php)
 * Copyright (c) 2019. J.Charika
 * Licensed under MIT (https://github.com/joecharika/hyper/master/LICENSE)
 */

namespace Hyper\Exception;


class ConfigNotFoundException extends HyperException
{
    public $message = 'Configuration file not found';
    public $code = 701;
}