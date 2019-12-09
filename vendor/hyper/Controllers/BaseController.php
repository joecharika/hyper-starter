<?php
/**
 * Hyper v1.0.2-beta.2 (https://hyper.starlight.co.zw)
 * Copyright (c) 2019. J.Charika
 * Licensed under MIT (https://github.com/joecharika/hyper/master/LICENSE)
 */

namespace Hyper\Controllers;

use Func\Twig\CompressExtension;
use Hyper\Application\{HyperApp, HyperEventHook, Request};
use Hyper\Database\DatabaseContext;
use Hyper\Exception\{HyperError, HyperException, HyperHttpException, NullValueException};
use Hyper\Files\Folder;
use Hyper\Functions\{Logger, Str};
use Hyper\Http\HttpMessage;
use Hyper\Utils\UserBrowser;
use Hyper\ViewEngine\Html;
use Twig\{Environment,
    Error\LoaderError,
    Error\RuntimeError,
    Error\SyntaxError,
    Extension\OptimizerExtension,
    Extension\SandboxExtension,
    Loader\FilesystemLoader,
    Sandbox\SecurityPolicy,
    TwigFilter,
    TwigFunction};
use function array_merge;
use function class_exists;
use function is_null;
use function json_encode;
use function str_replace;

/**
 * Class BaseController
 * @package hyper\Application
 */
class BaseController
{
    use HyperError;

    /** @var DatabaseContext */
    public $db;

    /** @var string */
    public $model, $modelName;

    /** @var string */
    public $name;

    /**
     * BaseController constructor.
     */
    public function __construct()
    {
        $this->name = $this->name ?? strtr(static::class, ['Controllers\\' => '', 'Controller' => '']);
        $this->model = $this->model ?? '\\Models\\' . Str::singular($this->name);
        $this->modelName = $this->modelName ?? Str::singular($this->name);

        if (class_exists($this->model))
            $this->db = new DatabaseContext($this->modelName);

    }

    /**
     * Convert $data to json and print it out
     * @param mixed $data
     * @return false|string
     */
    public function json($data)
    {
        if (is_null($data)) self::error(new NullValueException);
        return json_encode($data);
    }


    /**
     * @param string $view
     * @param null $model
     * @param string|HttpMessage|null $message
     * @param array $vars
     * @return string
     */
    public function view(string $view, $model = null, $message = null, $vars = [])
    {
        try {
            $view = strtolower(str_replace('.', '/', $view));
            $twig = new Environment(new FilesystemLoader(Folder::views()));

            HyperApp::event(HyperEventHook::renderingStarting, $twig);

            $this->addTwigExtensions($twig);
            $this->addTwigFilters($twig);
            $this->addTwigFunctions($twig);

            return $twig->render("$view.html.twig",
                array_merge(
                    [
                        'model' => $model,
                        'user' => HyperApp::$user,
                        'request' => (object)array_merge([
                            'url' => Request::url(),
                            'protocol' => Request::protocol(),
                            'path' => Request::path(),
                            'previousUrl' => Request::previousUrl(),
                            'query' => Request::query(),
                        ], Request::notification()),
                        'appName' => HyperApp::$name,
                        'appStorage' => (object)HyperApp::$storage,
                        'route' => Request::$route,
                        'notification' => $message instanceof HttpMessage
                            ? Request::notification($message->message, $message->type)
                            : Request::notification($message),
                        'html' => new Html()
                    ],
                    $vars
                )
            );

        } catch (LoaderError $e) {
            self::error(HyperHttpException::notFound($e->getMessage()));
        } catch (RuntimeError $e) {
            self::error(new HyperException($e->getMessage() . ' on line: ' . $e->getLine() . ' in ' . $e->getFile()));
        } catch (SyntaxError $e) {
            self::error(new HyperException($e->getMessage() . ' on line: ' . $e->getLine() . ' in ' . $e->getFile()));
        }

        return 'An error occurred while processing your request';
    }

    #region Extending Twig

    /**
     * @param Environment $twig
     */
    public function addTwigExtensions(Environment &$twig)
    {
        $twig->addExtension(new CompressExtension());
    }

    /**
     * @param Environment $twig
     */
    public function addTwigFilters(Environment &$twig)
    {
        #Cast object to array
        $twig->addFilter(new TwigFilter('toArray', function ($object) {
            return (array)$object;
        }));

        #Cast array to object
        $twig->addFilter(new TwigFilter('toObject', function ($array) {
            return (object)$array;
        }));

        #Cast array to object
        $twig->addFilter(new TwigFilter('isArray', function ($array) {
            return is_array($array);
        }));

        $twig->addFilter(new TwigFilter('toPascal', '\Hyper\Functions\Str::toPascal'));
        $twig->addFilter(new TwigFilter('toCamel', '\Hyper\Functions\Str::toCamel'));
        $twig->addFilter(new TwigFilter('plural', '\Hyper\Functions\Str::pluralize'));
        $twig->addFilter(new TwigFilter('singular', '\Hyper\Functions\Str::singular'));

        $twig->addFilter(new TwigFilter('browser', function ($ua) {
            foreach ((new UserBrowser)->commonBrowsers as $pattern => $name)
                if (preg_match("/" . $pattern . "/i", $ua, $match))
                    return strtolower($pattern);
            return 'hashtag';
        }));

        $twig->addFilter(new TwigFilter('take', function ($input, $length) {
            if (is_array($input)) {
                return array_slice($input, 0, $length);
            } elseif (is_string($input)) {
                return substr($input, 0, $length) . (strlen($input) > $length ? '...' : '');
            }
            return '-';
        }));
    }

    /**
     * @param Environment $twig
     */
    public function addTwigFunctions(Environment &$twig)
    {
        $url = Request::protocol() . '://' . Request::server();

        $twig->addFunction(new TwigFunction('img', function ($image, $path = 'img') use ($url) {
            return $url . "/assets/$path/$image";
        }));

        $twig->addFunction(new TwigFunction('base64', function ($image, $path = 'img') use ($url) {
            $file = Folder::assets() . "$path/$image";
            $var = base64_encode(file_get_contents($file));
            return "data:;base64,$var";
        }));

        $twig->addFunction(new TwigFunction('css', function ($stylesheet, $ext = 'css') {
            return $this->getAsset($stylesheet, $ext);
        }));

        $twig->addFunction(new TwigFunction('js', function ($script, $ext = 'js') {
            return $this->getAsset($script, $ext);
        }));

        $twig->addFunction(new TwigFunction('asset', function ($asset) use ($url) {
            return $url . '/assets/' . $asset;
        }));

        $twig->addFunction(new TwigFunction('get', function ($parent, $key, $match = 'id') {
            foreach ($parent as $object) {
                if ($object->$match === $key)
                    return $object;
            };

            return null;
        }));
    }

    #endregion

    #region Utils

    private function getAsset(string $_, $_t): string
    {
        $url = Request::protocol() . '://' . Request::server();
        $file = "{$_t}/{$_}" . ($_t ?? HyperApp::$debug ? ".$_t" : ".min.$_t");

        if (file_exists(Folder::assets() . $file)) {
            return "$url/assets/$file";
        }

        $file = "{$_t}/{$_}";

        if (Folder::assets() . "$file.$_t")
            $file = "$file.$_t";
        elseif (Folder::assets() . "$file.min.$_t")
            $file = "$file.min.$_t";
        else Logger::log("$_ file was not found.", Logger::ERROR);

        return "$url/assets/$file";
    }
    #endregion
}
