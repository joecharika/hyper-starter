<?php

/**
 * Hyper v1.0.2-beta.2 (https://hyper.starlight.co.zw)
 * Copyright (c) 2019. J.Charika
 * Licensed under MIT (https://github.com/joecharika/hyper/master/LICENSE)
 */

/**
 * 1. Create autoloader first
 *      [ ! ] Make sure composer is installed first
 *      => composer dump-autoload -o
 * 2. Include composer autoloader
 */
require __DIR__ . '/vendor/autoload.php';

/**
 * Import Hyper application
 * OR: new \Hyper\Application\HyperApp('app-name')
 */
use Hyper\Application\HyperApp;
use Hyper\Application\HyperEventHook;

/**
 * Create and run application
 * @param string $name
 * :: The name of your application
 *
 * @param string $routingMode
 * :: The method of routing used in your application. Default: auto
 * :: There are three modes: accessible from \Hyper\Routing\RoutingMode
 *      => auto
 *      => mixed
 *      => manual
 *
 * @param bool $usesAuth
 * :: Tell hyper application that you are going to use Hyper authentication
 *
 * @param HyperEventHook|null $eventsHook
 * :: Event hook to perform various action before or after hyper app reaches a certain stage
 * @example OnRouteCreatedEvent
 */
new HyperApp('SuperCoolAppName');

# CHEERS!!!
# Now watch your HyperApp do the magic
