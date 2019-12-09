<?php


namespace Hyper\Exception;


use Exception;
use Hyper\Application\HyperApp;
use Hyper\Controllers\BaseController;
use Hyper\Files\Folder;
use Hyper\Functions\{Arr, Logger, Obj, Str};
use Twig\{Environment, Error\Error, Loader\FilesystemLoader};
use function class_exists;

trait HyperError
{
    /**
     * @param Exception|string $error
     */
    public static function error($error)
    {
        if (!($error instanceof Exception)) {
            $var = @debug_backtrace()[0];

            $error = (new HyperException($error))
                ->setLine(@$var['line'])
                ->setFile(@$var['file']);
        }

        # Log error first
        Logger::log($log = <<<TEXT
                $error->code: $error->message
                
                ## Stacktrace      ::########################################################
                    {$error->getTraceAsString()}
                ## EndStacktrace   ::########################################################
            TEXT,
            Logger::ERROR
        );

        # Decide how to present error
        if (class_exists('\\Hyper\\Application\\HyperApp')) {
            $config = HyperApp::config();
            $log = $config->debug
                ? self::hyperError($error)
                : self::render(Obj::property($config->errors->custom, $error->code, $config->errors->default),
                    $error);
        } else $log = nl2br($log);

        print $log;
        exit(0);
    }

    /**
     * @param Exception $exception
     * @return string
     */
    private static function hyperError(Exception $exception): string
    {
        return self::render(
            @HyperApp::config()->errors->default ?? 'undefined_default_error_page',
            $exception,
            nl2br($exception->getTraceAsString() ?? ''),
            ' : ' . static::class
        );
    }

    public static function render(string $file, Exception $context, $trace = '', $title = ''): string
    {
        $twig = new Environment(new FilesystemLoader(Folder::views()));
        $baseController = new BaseController;
        $config = HyperApp::config();

        $baseController->addTwigExtensions($twig);
        $baseController->addTwigFunctions($twig);
        $baseController->addTwigFilters($twig);

        try {
            if (Str::contains($context->getFile(), 'Hyper\\'))
                $source = '<div class="page mixin">Source not available</div>';
            else {
                $lines = highlight_string(file_get_contents($context->getFile()), true);
                $lines = explode("<br />", $lines);
                $lines[((int)$context->getLine()) - 1] = "<div style='text-decoration: underline wavy red'>{$lines[((int)$context->getLine()) - 1]}</div>";

                $source = '';
                foreach ($lines as $key => $_line) {
                    $source .= '<span style="color: #cccccc">' . ++$key . ".&nbsp</span>$_line<br />";
                }
            }

            return $twig->render($file, [
                'title' => "Error $context->code" . $title,
                'report' => $config->reportLink ?? "#",
                'returnLink' => Arr::key($_SERVER, "HTTP_REFERER", "/"),
                'website' => Arr::key($_SERVER, "HTTP_HOST", 'unknown_site') . @$_SERVER['PATH_INFO'],
                'error' => (object)[
                    'message' => $context->message,
                    'code' => $context->getCode(),
                    'stackTrace' => $trace,
                    'file' => $context->getFile(),
                    'line' => $context->getLine(),
                    'source' => $source
                ],
                'app' => (object)[
                    'debug' => $config->debug ?? HyperApp::$debug
                ],
            ]);
        } catch (Error $e) {
            return $e->getMessage();
        }
    }
}