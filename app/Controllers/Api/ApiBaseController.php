<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UsersModel;
use App\Services\PhotoService;
use App\Services\S3Service;

class ApiBaseController extends BaseController
{
    use ApiResponse;

    protected ?\App\Models\UsersModel $usersModel = null;
    protected ?PhotoService $photoService = null;
    protected ?S3Service $s3 = null;

    /**
     * Lazy-load UsersModel
     */
    protected function getUsersModel(): UsersModel
    {
        if ($this->usersModel === null) {
            $this->usersModel = new UsersModel();
        }
        return $this->usersModel;
    }

    /**
     * Lazy-load PhotoService
     */
    protected function getPhotoService(): PhotoService
    {
        if ($this->photoService === null) {
            $this->photoService = new PhotoService();
        }
        return $this->photoService;
    }

    /**
     * Lazy-load S3Service
     */
    protected function getS3Service(): S3Service
    {
        if ($this->s3 === null) {
            $this->s3 = new S3Service();
        }
        return $this->s3;
    }

    /**
     * Get the current authenticated user's profile.
     * Returns the profile object or null.
     */
    protected function getCurrentUserProfile()
    {
        return $this->getUsersModel()->getUserInfo(user_id());
    }

    /**
     * Get current user profile or return 404 error response.
     * Usage: $profile = $this->requireUser(); if (!$profile) return;
     */
    protected function requireUser()
    {
        $profile = $this->getCurrentUserProfile();
        if (!$profile) {
            return null;
        }
        return $profile;
    }

    /**
     * Shorthand to get user profile and return error if not found.
     * Returns profile or sends error response (caller must return it).
     */
    protected function getUserOrError()
    {
        $profile = $this->getCurrentUserProfile();
        if (!$profile) {
            return $this->error('User tidak ditemukan', 404);
        }
        return $profile;
    }

    /**
     * Check if current user is admin or head.
     */
    protected function isAdmin(): bool
    {
        return in_groups('head') || in_groups('admin');
    }

    /**
     * Require admin/head role. Returns 403 error if not authorized.
     */
    protected function requireAdmin()
    {
        if (!$this->isAdmin()) {
            return $this->error('Akses ditolak. Hanya untuk admin.', 403);
        }
        return null; // Caller should check: $result = $this->requireAdmin(); if ($result) return $result;
    }

    /**
     * Get the profile photo URL, handling default photos.
     */
    protected function getProfilePhotoUrl(?string $filename): ?string
    {
        if (!$filename || $filename === 'default.jpg') {
            // Use local static default avatar instead of S3
            return base_url('images/default-avatar.svg');
        }
        return $this->getPhotoService()->getProfilePhotoUrl($filename);
    }

    /**
     * Get presensi photo URL.
     */
    protected function getPresensiPhotoUrl(string $type, ?string $filename): ?string
    {
        if (!$filename || $filename === '-') {
            return null;
        }
        return $this->getPhotoService()->getPresensiPhotoUrl($type, $filename);
    }

    /**
     * Enrich a user profile array with photo URLs.
     */
    protected function enrichUserProfile(array $data): array
    {
        $data['foto_url'] = $this->getProfilePhotoUrl($data['foto'] ?? null);
        return $data;
    }

    /**
     * Enrich presensi data with photo URLs.
     */
    protected function enrichPresensiData(array|object $item): array
    {
        if (is_object($item)) {
            $item = (array) $item;
        }
        $item['foto_masuk_url'] = $this->getPresensiPhotoUrl('masuk', $item['foto_masuk'] ?? null);
        $item['foto_keluar_url'] = $this->getPresensiPhotoUrl('keluar', $item['foto_keluar'] ?? null);
        return $item;
    }
}