<?php
/**
 * Hyper v1.0.2-beta.2 (https://hyper.starlight.co.zw)
 * Copyright (c) 2019. J.Charika
 * Licensed under MIT (https://github.com/joecharika/hyper/master/LICENSE)
 */

namespace Hyper\Controllers;

use Func\Twig\CompressExtension;
use Hyper\Twig\TwigFilters;
use Hyper\Twig\TwigFunctions;
use Hyper\Utils\FormBuilder;
use Hyper\Application\{HyperApp, HyperEventHook, Request};
use Hyper\Database\DatabaseContext;
use Hyper\Exception\{HyperError, HyperException, HyperHttpException, NullValueException};
use Hyper\Files\Folder;
use Hyper\Files\ImageHandler;
use Hyper\Functions\{Logger, Str};
use Hyper\Http\HttpMessage;
use Hyper\Utils\Html;
use Hyper\Utils\UserBrowser;
use Twig\{Environment, Error\Error, Error\LoaderError, Loader\FilesystemLoader, TwigFilter, TwigFunction};
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
            TwigFilters::attach($twig);
            TwigFunctions::attach($twig);

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
                        'app' => HyperApp::instance(),
                        'appStorage' => HyperApp::$storage,
                        'route' => Request::$route,
                        'notification' => $message instanceof HttpMessage
                            ? Request::notification($message->message, $message->type)
                            : Request::notification($message),
                        'html' => new Html(true),
                        'form' => new FormBuilder()
                    ],
                    $vars
                )
            );

        } catch (LoaderError $e) {
            self::error(HyperHttpException::notFound($e->getMessage()));
        } catch (Error $e) {
            self::error(new HyperException($e->getMessage() . ' on line: ' . $e->getLine() . ' in ' . $e->getFile()));
        } catch (\Exception $e){
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

    #endregion
}
