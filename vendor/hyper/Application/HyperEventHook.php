<?php
/**
 * Hyper v1.0.2-beta.2 (https://hyper.starlight.co.zw)
 * Copyright (c) 2019. J.Charika
 * Licensed under MIT (https://github.com/joecharika/hyper/master/LICENSE)
 */

namespace Hyper\Application;


use Error;
use Exception;
use Hyper\Exception\HyperError;
use Hyper\Exception\HyperException;
use RuntimeException;
use function array_key_exists;
use function array_search;

class HyperEventHook
{
    use HyperError;

    const boot = 'onBoot',
        booted = 'onBooted',
        routingStarting = 'onRoutingStarting',
        routeCreated = 'onRouteCreated',
        routingCompleted = 'onRoutingCompleted',
        renderingStarting = 'onRenderingStarting',
        error = 'onError',
        renderingCompleted = 'onRenderingCompleted';

    private $definedHooks = [
        self::boot,
        self::booted,
        self::routeCreated,
        self::routingStarting,
        self::routingCompleted,
        self::renderingStarting,
        self::renderingCompleted,
        self::error
    ];

    private $events = [];

    public function __construct(array $events)
    {
        if (array_search('onError', array_keys($events)) === false)
            $this->events['onError'] = function (Event $event) {
                /** @var HyperException $exc */
                $exc = $event->data;

                if ($exc instanceof HyperException) {
                    self::error($exc);
                } else {
                    if ($exc instanceof Exception || $exc instanceof RuntimeException || $exc instanceof Error)
                        $exc = $exc->getMessage();
                    self::error(new HyperException('<small><i>A hyper unrelated error occurred, and all we know is this:</i></small> <br> ' . $exc));
                }
            };
        $handler = function ($exc = null) {
            $this->events['onError'](new Event('onError', $exc));
        };

        if (!HyperApp::$debug)
            set_error_handler($handler);

        set_exception_handler($handler);

        foreach ($events as $event => $function) {
            if (array_search($event, $this->definedHooks) !== false) {
                $this->events[$event] = $function;
            } else self::error('Unknown event hook: ' . $event);
        }
    }

    public function emit($eventName, $data = null)
    {
        if (array_key_exists($eventName, $this->events))
            $this->events[$eventName](new Event($eventName, $data));
    }
}