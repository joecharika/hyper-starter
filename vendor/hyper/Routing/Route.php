<?php

namespace Hyper\Routing;


use Hyper\Application\HyperApp;
use Hyper\Application\HyperEventHook;
use Hyper\Application\Request;

/**
 * Class Route
 * @package Hyper\Routing
 * @uses \Hyper\Application\HyperApp, \Hyper\Application\Request, \Hyper\Application\HyperEventHook
 */
class Route
{
    /**
     * @var
     */
    public $id;
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $path;
    /**
     * @var string
     */
    public $controller;

    /** @var string */
    public $realController;
    /**
     * @var string
     */
    public $action;

    /**
     * Route constructor.
     * @param string $action
     * @param string $controller
     * @param string $path
     * @param string $name
     */
    public function __construct(string $action, string $controller, string $path, string $name)
    {
        $this->id = uniqid();
        $this->name = $name;
        $this->path = $path;
        $this->controller = $controller;
        $this->action = $action;

        $this->realController = strtr(strtolower($controller), [
            'controller' => '',
            '\\' => '',
            'controllers' => ''
        ]);

        HyperApp::event(HyperEventHook::routeCreated, $this);
    }

    /**
     * Checks if given route matches the currently visited route
     * @param $route
     * @return bool
     */
    public static function match(Route $route): bool
    {
        return (Request::route()->controller === $route->controller) && (Request::route()->action === $route->action);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "$this->id: $this->controller/$this->action";
    }
}