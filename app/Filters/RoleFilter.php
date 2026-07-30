<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Myth\Auth\Filters\RoleFilter as MythRoleFilter;

class RoleFilter extends MythRoleFilter
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!$this->authenticate->check()) {
            session()->set('redirect_url', current_url());

            return redirect($this->reservedRoutes['login']);
        }

        if (empty($arguments)) {
            return;
        }

        foreach ($arguments as $group) {
            if ($this->authorize->inGroup($group, $this->authenticate->id())) {
                return;
            }
        }

        return redirect()->to('/overview');
    }
}
