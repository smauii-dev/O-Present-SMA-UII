<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class ApiCors implements FilterInterface
{
    private function getAllowedOrigin(RequestInterface $request): string
    {
        $origin = $request->getHeaderLine('Origin');
        $allowed = [
            'https://app.smauiiyk.sch.id',
            'https://presensi.smauiiyk.sch.id',
            'http://localhost:5173',
            'http://localhost:5174',
            'http://localhost:8080',
            'http://localhost:8100',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:8100',
        ];

        // Optional extra origins from env (comma-separated)
        $extra = env('cors.allowedOrigins', '');
        if (is_string($extra) && $extra !== '') {
            foreach (array_map('trim', explode(',', $extra)) as $o) {
                if ($o !== '') {
                    $allowed[] = $o;
                }
            }
        }
        if (in_array($origin, $allowed)) {
            return $origin;
        }
        return $allowed[0];
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        // Handle preflight OPTIONS
        if ($request->getMethod() === 'OPTIONS') {
            $origin = $this->getAllowedOrigin($request);

            return service('response')
                ->setStatusCode(204)
                ->setHeader('Access-Control-Allow-Origin', $origin)
                ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->setHeader('Access-Control-Allow-Credentials', 'true')
                ->setHeader('Access-Control-Max-Age', '86400');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $origin = $this->getAllowedOrigin($request);

        $response->setHeader('Access-Control-Allow-Origin', $origin);
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->setHeader('Access-Control-Allow-Credentials', 'true');
    }
}
