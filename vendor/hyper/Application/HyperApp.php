<?php

namespace Hyper\Application;

use Hyper\Database\DatabaseConfig;
use Hyper\Exception\{HyperError, HyperHttpException};
use Hyper\Functions\Logger;
use Hyper\Models\User;
use Hyper\Reflection\Annotation;
use Hyper\Routing\{Route};
use function array_search;
use function explode;
use function file_exists;
use function method_exists;
use function uniqid;

/**
 * Class HyperApp
 * @package hyper\Application
 */
class HyperApp
{
    use HyperError; #TODO: HyperWeb,HyperApi, HyperOAuth;

    #region Properties
    /**
     * Name of application
     * @var string
     */
    public static $name = 'HyperApp';

    /**
     * @var HyperApp
     */
    protected static $instance;

    /**
     * Available routes
     * @var Route[]
     */
    public static $routes;

    /**
     * Signed-in user
     * @var User
     */
    public static $user;

    /**
     * Current debug state
     * @var bool
     */
    public static $debug = true;

    /**
     * Database configuration object
     * @var DatabaseConfig
     */
    public static $dbConfig;

    /**
     * Event hooks
     * @var HyperEventHook|null
     */
    public static $eventHook;

    /**
     * Temporary static app storage,
     * @var array
     */
    public static $storage = [];

    #endregion

    #region Init
    /**
     * HyperApp constructor.
     * @param string $name The name of your application
     * @param string $routingMode The method of routing used in your application. Default: auto
     * @param bool $usesAuth
     * @param HyperEventHook|null $eventsHook
     */
    public function __construct(string $name, string $routingMode = 'auto', $usesAuth = false, HyperEventHook $eventsHook = null)
    {
        # Set the application instance for global access
        self::$instance = $this;

        # initialize the event hook first
        self::$eventHook = $eventsHook;

        # Emit HyperEventHook::onBoot event => booting starting
        $this->event(
            HyperEventHook::boot,
            'Application is ready to start'
        );

        #Initialize app data
        HyperApp::$debug = self::config()->debug;
        HyperApp::$dbConfig = new DatabaseConfig();
        HyperApp::$name = $name ?? self::$name;

        # Clear last request queries
        Logger::log('', '__INIT__', 'LAST_REQUEST_QUERY', 'w');

        # Initialize authentication if required
        HyperApp::$user = $usesAuth ? (new Authorization())->getSession()->user : new User();

        # Emit HyperEventHook::onBooted event => booting completed
        $this->event(HyperEventHook::booted, 'Application has been initialised successfully');

        # Run application
        $this->run([]);

    }

    #endregion

    /**
     * Trigger an event
     * @param string $event Name od the event
     * @param mixed|null $data Data to pass to the event
     * @return void
     */
    public static function event(string $event, $data = null): void
    {
        if (!isset(self::$eventHook))
            self::$eventHook = new HyperEventHook([]);

        self::$eventHook->emit($event, $data);
    }

    /**
     * Get configuration object from specified file
     * @param string $file default => 'hyper.config.json'
     * @return object|null
     */
    public static function config($file = 'hyper.config.json')
    {
        #If config file was not found return default config
        if (!file_exists($file) || !isset($file)) {
            return (object)[
                'debug' => true,
                'errors' => (object)[
                    'defaultPath' => 'shared/error.html.twig',
                    'custom' => (object)[]
                ],
                'reportLink' => null
            ];
        }

        #Else return the config from config file
        return (object)json_decode(file_get_contents($file));
    }

    /**
     * @param array $routes
     */
    protected function run(array $routes)
    {
        /** @var Route $route */
        $route = null;

        foreach ($routes as $tempRoute) {
            if (Route::match($tempRoute)) {
                $route = Request::$route = $tempRoute;
                break;
            }
        }

        if (!isset($route))
            $route = Request::$route =
                new Route(
                    Request::route()->action,
                    Request::route()->controller,
                    Request::path(),
                    uniqid()
                );

        switch ($route->realController) {
            case 'api':
                $this->api($route);
                break;
            case 'oauth':
                $this->oauth($route);
                break;
            case 'bot':
                $this->bot($route);
                break;
            case 'config':
                $this->configUI();
                break;
            default:
                $this->web($route);
        }
    }

    #region Application Types

    protected function api(Route $route)
    {
        self::error('Api is not available');
    }

    protected function oauth(Route $route)
    {
        self::error('OAuth is not available');
    }

    protected function bot(Route $route)
    {
        self::error('Bot is not available');
    }

    protected function configUI()
    {
        self::error('Configuration UI is not available');
    }

    /**
     * @param Route $route
     */
    protected function web(Route $route): void
    {
        $ext = Request::isPost() ? 'post' : 'get';
        $route->action = Request::isPost() ? $ext . $route->action : $route->action;

        if (class_exists($route->controller)) {

            if (method_exists($route->controller, $ext . Request::$route->action) || method_exists($route->controller,
                    Request::$route->action)) {
                if ($this->validate($route)) {
                    $action = $route->action;
                    echo (new $route->controller())->$action();
                } else
                    Request::redirectTo('login', 'auth');

            } else
                self::error(
                    HyperHttpException::notFound("Controller action <span style='color: red'>( $route->controller::$route->action )</span> not found")
                );
        } else
            self::error(
                HyperHttpException::notFound("Controller <span style='color: red'>( $route->controller )</span> not found")
            );
    }

    /**
     * Validate route against user auth status
     * @param $route
     * @return bool
     */
    protected function validate($route)
    {
        # Initialize validators
        $action = $route->action;
        $controller = $route->controller;
        $controllerAllowedRoles = Annotation::getClassAnnotation($controller, 'Authorize');
        $actionAllowedRoles = Annotation::getMethodAnnotation($controller, $action, 'Authorize');

        # Validate request
        if (!isset($actionAllowedRoles) && !isset($controllerAllowedRoles))
            return true;
        else {
            $roles = isset($actionAllowedRoles)
                ? explode('|', $actionAllowedRoles)
                : (!isset($controllerAllowedRoles) ? [] : explode('|', $controllerAllowedRoles));

            if ($roles == [true])
                if (User::isAuthenticated())
                    return true;

            if (array_search(isset(self::$user) ? self::$user->role : User::getRole(), $roles) !== false)
                return true;
        }
        return false;
    }

    /**
     * Get application instance
     * @return HyperApp
     */
    public static function instance(): HyperApp
    {
        return self::$instance;
    }

    #endregion
}
