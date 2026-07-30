<?php

namespace App\Debug;

use CodeIgniter\Debug\BaseExceptionHandler;
use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Nongbit\Twig\Twig;
use Throwable;

class TwigExceptionHandler extends BaseExceptionHandler implements ExceptionHandlerInterface
{
    public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode,
    ): void {
        if ($request instanceof IncomingRequest) {
            try {
                $response->setStatusCode($statusCode);
            } catch (\CodeIgniter\HTTP\Exceptions\HTTPException) {
                $statusCode = 500;
                $response->setStatusCode($statusCode);
            }

            if (! headers_sent()) {
                header(
                    sprintf(
                        'HTTP/%s %s %s',
                        $request->getProtocolVersion(),
                        $response->getStatusCode(),
                        $response->getReasonPhrase(),
                    ),
                    true,
                    $statusCode,
                );
            }

            // Non-HTML requests get JSON.
            if (! str_contains($request->getHeaderLine('accept'), 'text/html')) {
                $data = ENVIRONMENT === 'development'
                    ? $this->collectVars($exception, $statusCode)
                    : ['error' => 'Server Error', 'code' => $statusCode];

                $response->setBody(json_encode($data, JSON_UNESCAPED_UNICODE));
                $response->setContentType('application/json');
                $response->send();

                if (ENVIRONMENT !== 'testing') {
                    exit($exitCode);
                }

                return;
            }
        }

        // HTML requests get Twig error pages.
        try {
            $twig = Twig::getInstance();

            // Register Vite helper for asset resolution.
            $twig->addFunctions([
                'vite' => function (string $entry) {
                    $manifestPath = ROOTPATH . 'public/build/.vite/manifest.json';
                    if (! is_file($manifestPath)) {
                        return '/assets/' . $entry;
                    }
                    $manifest = json_decode(file_get_contents($manifestPath), true);

                    $key = 'resources/' . $entry;
                    if (isset($manifest[$key]['file'])) {
                        return '/' . $manifest[$key]['file'];
                    }
                    if (isset($manifest[$entry]['file'])) {
                        return '/' . $manifest[$entry]['file'];
                    }
                    foreach ($manifest as $k => $v) {
                        if (isset($v['src']) && basename($v['src']) === $entry) {
                            return '/' . $v['file'];
                        }
                    }
                    return '/assets/' . $entry;
                },
                'csrf_hash' => function () {
                    return csrf_hash();
                },
                'vite_css' => function (string $entry) {
                    $manifestPath = ROOTPATH . 'public/build/.vite/manifest.json';
                    if (! is_file($manifestPath)) {
                        return '';
                    }
                    $manifest = json_decode(file_get_contents($manifestPath), true);

                    $key = 'resources/' . $entry;
                    $cssFiles = $manifest[$key]['css'] ?? $manifest[$entry]['css'] ?? [];

                    if (! $cssFiles) {
                        foreach ($manifest as $k => $v) {
                            if (isset($v['src']) && basename($v['src']) === $entry) {
                                $cssFiles = $v['css'] ?? [];
                                break;
                            }
                        }
                    }

                    $tags = [];
                    foreach ($cssFiles as $css) {
                        $tags[] = '<link rel="stylesheet" href="/' . $css . '">';
                    }

                    return implode("\n  ", $tags);
                },
            ]);

            $template = match (true) {
                $exception instanceof PageNotFoundException,
                $statusCode === 404 => 'errors/404',
                default => 'errors/500',
            };

            echo $twig->render($template, [
                'statusCode' => $statusCode,
                'message'    => ENVIRONMENT === 'development' ? $exception->getMessage() : null,
            ]);

            if (ENVIRONMENT !== 'testing') {
                exit($exitCode);
            }

            return;
        } catch (Throwable) {
            // Twig failed — fall through to raw-PHP fallback.
        }

        // Fallback: use CI4's built-in raw-PHP error pages.
        $fallback = new \CodeIgniter\Debug\ExceptionHandler($this->config);
        $fallback->handle($exception, $request, $response, $statusCode, $exitCode);
    }
}
