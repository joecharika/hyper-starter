<?php


namespace Hyper\Application;

use Hyper\Database\DatabaseContext;
use Hyper\Exception\{HyperError, HyperHttpException, NullValueException};
use Hyper\Functions\{Arr, Obj, Str};
use Hyper\Http\HttpMessage;
use Hyper\Reflection\Annotation;
use Hyper\Routing\Route;
use Hyper\SQL\SqlOperator;
use function array_slice;
use function count;
use function explode;
use function header;
use function is_string;
use function property_exists;
use function strlen;
use function strtolower;

/**
 * Class Request
 * @package Hyper\Application
 */
class Request
{
    use HyperError;

    /** @var Route */
    public static $route;


    /**
     * @return string
     */
    public static function url()
    {
        return Request::protocol() . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    /**
     * @return string
     */
    public static function protocol()
    {
        return strtolower(@explode('/', $_SERVER['SERVER_PROTOCOL'])[0]);
    }

    /**
     * @return string
     */
    public static function server()
    {
        return $_SERVER['HTTP_HOST'];
    }

    /**
     * @return string
     */
    public static function host()
    {
        return $_SERVER['localhost'];
    }

    /**
     * @return string
     */
    public static function port()
    {
        return $_SERVER['SERVER_PORT'];
    }

    public static function requestUri()
    {
        return Arr::key($_SERVER, 'REQUEST_URI', '/');
    }

    public static function previousUrl()
    {
        return Arr::key($_SERVER, 'HTTP_REFERER', '/');
    }

    /**
     * @param null $message
     * @param string $type
     * @return array
     */
    public static function notification($message = null, $type = null)
    {
        return [
            'hasMessage' => isset($message) ? true : Request::hasMessage(),
            'message' => $message ?? Request::message()->message,
            'messageType' => $type ?? Request::message()->type
        ];
    }

    /**
     * @return bool
     */
    public static function hasMessage()
    {
        return array_key_exists('message', $_GET);
    }

    /**
     * @return HttpMessage
     */
    public static function message(): HttpMessage
    {
        return new HttpMessage(
            Obj::property(Request::get(), 'message', ''),
            Obj::property(Request::get(), 'messageType', '')
        );
    }

    /**
     * @return object
     */
    public static function get()
    {
        return (object)$_GET;
    }

    /**
     * @return mixed
     */
    public static function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @return bool
     */
    public static function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    /**
     * Checks if given route matches the currently visited route
     * @param $route
     * @return bool
     */
    public static function matchUrl(string $route)
    {
        $params = explode("/", strtolower("$route"));

        return strtolower(Request::route()->controller) === Arr::key($params, 1, 'home')
            && strtolower(Request::route()->action) === Arr::key($params, 2, 'index');
    }

    /**
     * @return Route
     */
    public static function route(): Route
    {
        $path = Request::path();
        $pathAsArray = explode('/', $path);

        $action = Arr::key($pathAsArray, 2, 'Index');
        $action = empty($action) ? 'Index' : $action;

        $controller = Arr::key($pathAsArray, 1, 'Home');
        $controller = empty($controller) ? 'Home' : $controller;

        return new Route(
            ucfirst(Str::toPascal($action, '-')),
            '\\Controllers\\' . ucfirst($controller) . 'Controller',
            $path,
            uniqid()
        );
    }

    public static function path()
    {
        return Arr::key(explode('?', Arr::key($_SERVER, 'REQUEST_URI', '/')), 0);
    }

    /**
     * @return object
     */
    public static function query()
    {
        $array_merge = array_key_exists('page', $_GET) ? $_GET : array_merge($_GET, ['page' => 1]);
        return (object)$array_merge;
    }

    /**
     * Binds given object to Request post/get params
     *
     * @param object|null $object The object to bind
     * @return object The same object with values from the request
     */
    public static function bind($object): object
    {
        if (!isset($object)) return (object)[];

        $class = strtolower(get_class($object));
        $properties = get_class_vars($class);

        $object = (array)$object;

        foreach ($properties as $property => $value) {
            if (property_exists(Request::data(), $property)) {
                if (Annotation::getPropertyAnnotation($class, $property, 'isFile')) {
                    $hasFile = !empty(Obj::property(Request::files(), $property)['tpm_name']);
                    $object[$property] = $hasFile ? Request::files()->$property : Request::data()->$property;
                } else $object[$property] = Request::data()->$property;
            }
        }

        return (object)$object;
    }

    public static function data()
    {
        return Request::isPost() ? Request::post() : Request::get();
    }

    /**
     * @return bool
     */
    public static function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] == "POST";
    }

    /**
     * @return object
     */
    public static function post()
    {
        return (object)$_POST;
    }

    /**
     * @return object
     */
    public static function files()
    {
        return (object)$_FILES;
    }

    /**
     * Redirect to a controller action within the application
     *
     * @param $action
     * @param $controller
     * @param string|null $param
     * @param HttpMessage|string|null $message
     * @param array $query
     * @return string
     */
    public static function redirectTo($action, $controller, $param = null, $message = null, $query = [])
    {
        $message = is_string($message) ? new HttpMessage($message) : $message;

        $message = isset($message) ? (isset($message->message) ? "?$message" : '') : '';

        $query = Arr::spread((array)$query, true, '&', '=');

        $query = empty($message) ? (empty($query) ? '' : "?$query") : $query;

        $param = !isset($param) ? '' : $param;
        $action = $action === 'index' ? '' : "$action/";

        header("Location: /$controller/$action$param$message$query");
        return 'Redirecting...';
    }

    /**
     * Redirect to a given url with a message query
     *
     * @param $url
     * @param HttpMessage|string $message
     * @param array $query
     */
    public static function redirectToUrl($url, $message = null, $query = [])
    {
        $message = is_string($message) ? new HttpMessage($message) : $message;

        $message = isset($message) ? (Str::contains($url, '?') ? '&' : '?') . "$message" : '';

        if (isset($url))
            header("Location: $url$message");
        else self::error(new NullValueException);
    }

    /**
     * Get a model from the submitted id parameter (/{controller}/{action}/{id})
     * @param null $parents
     * @param null $lists
     * @return object|null
     */
    public static function fromParam($parents = null, $lists = null)
    {
        $model = null;

        if (!is_null(@Request::params()->id) or !is_null(@Request::data()->id)) {
            $model = (new DatabaseContext(Str::singular(Request::$route->realController)))
                ->first('id', Request::params()->id ?? Request::data()->id, SqlOperator::equal, $parents, $lists);

            if (!isset($model)) self::error(HyperHttpException::notFound());
        } else self::error(HyperHttpException::badRequest());

        return $model;
    }

    /**
     * @return object
     */
    public static function params()
    {
        $params = explode('/', self::path());

        $id = Arr::key($params, 3, null);
        $id = strlen($id) === 0 ? null : $id;

        $obj = ['id' => $id];

        if (count($params) > 3) {
            foreach (array_slice((array)$params, 4) as $key => $value) {
                $obj["param$key"] = $value;
            }
        }

        return (object)$obj;
    }
}
