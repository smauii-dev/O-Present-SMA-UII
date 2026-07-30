<?php

namespace App\Controllers\Api\Admin;

use App\Controllers\Api\ApiBaseController;

/**
 * List Myth/Auth groups for admin forms (pegawai create/edit).
 */
class Role extends ApiBaseController
{
    public function index()
    {
        if ($err = $this->requireAdmin()) {
            return $err;
        }

        $db = \Config\Database::connect();
        $rows = $db->table('auth_groups')
            ->select('id, name, description')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        return $this->success($rows);
    }
}
