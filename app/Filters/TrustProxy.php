<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class TrustProxy implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Trust X-Forwarded-Proto header from nginx proxy
        // If the original request was HTTPS, tell CI4 it's a secure request
        $forwardedProto = $request->getServer('HTTP_X_FORWARDED_PROTO');
        
        if ($forwardedProto === 'https') {
            // Directly modify $_SERVER - setGlobal() replaces entire array!
            $_SERVER['HTTPS'] = 'on';
            $_SERVER['SERVER_PORT'] = '443';
        }
        
        // IMPORTANT: Do NOT change REMOTE_ADDR - isFromTrustedProxy() 
        // checks if the connecting IP (nginx) is in proxyIPs range.
        // If we change it to client IP, the proxy trust check fails.
        
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}