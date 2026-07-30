<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Injects ci-xhr-guard.js before CI Debug Toolbar's toolbarloader so
 * window.oldXHR cannot be overwritten to a wrapper (infinite newXHR recursion).
 */
class DebugbarXhrGuard implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (ENVIRONMENT === 'testing' || is_cli()) {
            return null;
        }

        $contentType = $response->getHeaderLine('Content-Type');
        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return null;
        }

        $body = (string) $response->getBody();
        if ($body === '' || ! str_contains($body, 'debugbar_loader')) {
            return null;
        }

        // Already injected
        if (str_contains($body, 'ci-xhr-guard')) {
            return null;
        }

        // Inline so it always runs before toolbarloader (no async race on external src).
        $guardPath = FCPATH . 'js/ci-xhr-guard.js';
        if (! is_file($guardPath)) {
            return null;
        }

        $guardJs = file_get_contents($guardPath);
        if ($guardJs === false || $guardJs === '') {
            return null;
        }

        $guard = '<script id="ci-xhr-guard">' . $guardJs . '</script>';

        // Prefer immediately before the debugbar loader script
        if (str_contains($body, 'id="debugbar_loader"')) {
            $body = preg_replace(
                '/<script[^>]*id="debugbar_loader"/',
                $guard . '<script id="debugbar_loader"',
                $body,
                1
            );
        } elseif (str_contains($body, '<head>')) {
            $body = preg_replace('/<head>/', '<head>' . $guard, $body, 1);
        } else {
            return null;
        }

        $response->setBody($body);

        return null;
    }
}
