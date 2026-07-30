<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ForcePasswordReset implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = service('authentication');

        // Use isLoggedIn() instead of check() — check() throws RedirectException
        // which doesn't exist in CI4 4.7+, breaking all authenticated requests.
        if (! $auth->isLoggedIn()) {
            // If this filter is used as a standalone auth guard (e.g. profile routes),
            // redirect unauthenticated users to login.
            if (! empty($arguments) && in_array('requireAuth', $arguments)) {
                session()->set('redirect_url', current_url());

                return redirect()->to('/login');
            }

            return;
        }

        $user = $auth->user();
        
        // Skip if force_pass_reset is not set
        if (empty($user->force_pass_reset)) {
            return;
        }

        // Allow access to password change page and logout
        $uri = $request->getUri()->getPath();
        $allowedPaths = ['/profile', '/profile/password', '/logout', '/reset-password'];
        
        foreach ($allowedPaths as $path) {
            if (str_starts_with($uri, $path)) {
                return;
            }
        }

        // Allow API endpoints for password change
        if (str_starts_with($uri, '/api/profile/password') || str_starts_with($uri, '/api/logout')) {
            return;
        }

        // Redirect to profile password change page
        return redirect()->to('/profile')->with('warning', 'Anda wajib mengganti password default sebelum melanjutkan.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}