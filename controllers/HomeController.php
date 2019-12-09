<?php

/**
 * Hyper v1.0.0-beta.2 (https://hyper.starlight.co.zw)
 * Copyright (c) 2019. J.Charika
 * Licensed under MIT (https://github.com/joecharika/hyper/master/LICENSE)
 */

namespace Controllers;

use Hyper\Controllers\Controller;
use Hyper\Exception\HyperException;
use Hyper\Http\HttpMessage;

/**
 * Class HomeController
 * Example of a basic controller for the urls:
 *      => / | /home | /home/index,  and
 *      => /home/{action*}
 * @package Controllers
 */
class HomeController extends Controller
{
    /**
     * An action that responds to the following urls
     * @note Ignoring 'home' only works with the base-view|site-root
     * @note Ignoring 'index' works on all index actions
     * @route / => /home => /home/index
     * @return string
     */
    public function index(): string
    {
        /**
         *
         * Return the view you want as
         * folder.view-name or folder/view-name
         * Complied to
         *      => views/{folder}/{view-name}.html.twig
         *******************************************************
         * @param string $view
         * :: The view to render as explained above
         *
         * @param mixed|null $model
         * :: Base model to complete the MVC circle
         * :: Accessible as {{ model }}
         * :: Can be anything
         *
         * @param string|HttpMessage|null $message
         * :: Pass a message to the view
         *
         * @param array $vars
         * :: context variable as:
         * @return string
         * :: Complied page ready for rendering
         * @example [ 'myVariable' => 'AwesomeValue' ]
         * :: the above will be accessible in a view as {{ myVariable }}
         *
         */
        return $this->view('home.index');
    }

    public function about(): string
    {
        return $this->view('home.about');
    }

    /**
     * @throws HyperException
     */
    public function contact()
    {
        throw new HyperException('Contact unimplemented', 404);
    }
}
