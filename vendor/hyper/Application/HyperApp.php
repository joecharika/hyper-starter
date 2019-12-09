<?php

namespace Hyper\Application;

use Hyper\Database\DatabaseConfig;
use Hyper\Exception\{HyperError, HyperException, HyperHttpException};
use Hyper\Functions\Logger;
use Hyper\Functions\Str;
use Hyper\Http\Cookie;
use Hyper\Http\StatusCode;
use Hyper\Models\User;
use Hyper\Reflection\Annotation;
use Hyper\Routing\{Route};
use Hyper\Utils\General;
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
     * Temporary static app storage,
     * @var array
     */
    public static $storage = [];
    /**
     * @var HyperApp
     */
    protected static $instance;
    /**
     * Name of application
     * @var string
     */
    public $name = 'HyperApp';
    /**
     * Available routes
     * @var Route[]
     */
    public $routes;
    /**
     * Event hooks
     * @var HyperEventHook|null
     */
    public $eventHook;

    #endregion

    #region Init

    /**
     * HyperApp constructor.
     * @param string $name The name of your application
     * @param bool $usesAuth
     * @param HyperEventHook|null $eventsHook
     */
    public function __construct(string $name, $usesAuth = false, HyperEventHook $eventsHook = null)
    {
        # Set the application instance for global access
        self::$instance = $this;

        $this->ddos();

        # initialize the event hook first
        $this->eventHook = $eventsHook;

        # Emit HyperEventHook::onBoot event => booting starting
        $this->event(
            HyperEventHook::boot,
            'Application is ready to start'
        );

        # Initialize app data
        HyperApp::$debug = self::config()->debug;
        HyperApp::$dbConfig = new DatabaseConfig();
        $this->name = $name ?? $this->name;

        # Clear last request queries
        Logger::log('', '__INIT__', 'LAST_REQUEST_QUERY', 'w');

        # Initialize authentication if required
        HyperApp::$user = $usesAuth
            ? (new Authorization())->getSession()->user
            : new User;

        # Emit HyperEventHook::onBooted event => booting completed
        $this->event(
            HyperEventHook::booted,
            'Application has been initialised successfully'
        );

        # Run application
        $this->run();
    }

    protected function ddos()
    {
        $config = self::config();
        $ipAddress = General::ipAddress();

        if ($config->limitRequests && $ipAddress) {
            $cookie = new Cookie;
            $ddosKey = '__hyper-piXhjs984Mhfo::f8Hdksm';
            $ddosKeyPair = $cookie->getCookie($ddosKey);

            if (Str::endsWith($ddosKeyPair, '.010')) {
                $cookie->removeCookie($ddosKey);
                header('refresh:7;url=' . Request::url(), false, StatusCode::TOO_MANY_REQUESTS);
                self::error(new HyperException(
                    'Your consistence is amazing, but lets take a break...',
                    StatusCode::TOO_MANY_REQUESTS
                ));
            } else {
                $cookie->addCookie(
                    $ddosKey,
                    hash('gost-crypto', $ipAddress) . '.0' . ((int)substr($ddosKeyPair, -2) + 1),
                    time() + 10,
                    '/'
                );
            }
        }
    }
    #endregion

    /**
     * Get configuration object from specified file
     * @param string $file default => 'hyper.config.json'
     * @return object|null
     */
    public static function config($file = 'hyper.config.json')
    {
        #If config file was not found return default config
        if (!file_exists($file) || !isset($file))
            return (object)[
                'debug' => true,
                'limitRequests' => true,
                'errors' => (object)[
                    'defaultPath' => 'shared/error.html.twig',
                    'custom' => (object)[]
                ],
                'reportLink' => null
            ];

        #Else return the config from config file
        return (object)json_decode(file_get_contents($file));
    }

    /**
     * Trigger an event
     * @param string $event Name od the event
     * @param mixed|null $data Data to pass to the event
     * @return void
     */
    public static function event(string $event, $data = null): void
    {
        $instance = HyperApp::instance();
        if (!isset($instance->eventHook))
            $instance->eventHook = new HyperEventHook([]);

        $instance->eventHook->emit($event, $data);
    }

    /**
     * Get application instance
     * @return HyperApp
     */
    public static function instance(): HyperApp
    {
        return self::$instance;
    }

    #region Application Types

    protected function run()
    {
        /** @var Route $route */
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
                    Request::redirectTo('login', 'auth', null, null, ['return' => Request::path()]);

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

    #endregion
}
