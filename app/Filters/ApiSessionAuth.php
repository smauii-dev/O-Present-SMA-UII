<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class ApiSessionAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authenticate = service('authentication');

        if (!$authenticate->check()) {
            return service('response')
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Belum login atau session expired',
                ])
                ->setStatusCode(401);
        }

        // Role check if arguments provided
        if (!empty($arguments)) {
            $userId = $authenticate->id();
            $authorize = service('authorization');

            foreach ($arguments as $group) {
                if ($authorize->inGroup($group, $userId)) {
                    return; // Authorized
                }
            }

            return service('response')
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Tidak memiliki akses',
                ])
                ->setStatusCode(403);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
