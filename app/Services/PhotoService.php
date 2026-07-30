<?php

namespace App\Services;

class PhotoService
{
    protected S3Service $s3;

    public function __construct()
    {
        $this->s3 = new S3Service();
    }

    /**
     * Decode base64 image from request data.
     */
    public function decodeBase64Image(string $base64): ?string
    {
        $data = preg_replace('/^data:image\/(jpeg|png);base64,/', '', $base64);
        $decoded = base64_decode($data, true);
        return $decoded === false ? null : $decoded;
    }

    /**
     * Upload a presensi photo (clock-in or clock-out).
     */
    public function uploadPresensiPhoto(string $type, string $username, string $base64): array
    {
        $foto = $this->decodeBase64Image($base64);
        if ($foto === null) {
            throw new \RuntimeException('Format foto tidak valid');
        }

        $nama_foto = $type . '-' . date('Y-m-d-H-i-s') . '-' . $username . '.png';
        $s3Key = 'presensi/' . $type . '/' . $nama_foto;

        $this->s3->upload($s3Key, $foto, 'image/png');

        return ['filename' => $nama_foto, 'key' => $s3Key];
    }

    /**
     * Upload a profile photo.
     */
    public function uploadProfilePhoto(string $username, string $base64): array
    {
        $foto = $this->decodeBase64Image($base64);
        if ($foto === null) {
            throw new \RuntimeException('Format foto tidak valid');
        }

        $nama_foto = 'profile-' . date('Y-m-d-H-i-s') . '-' . $username . '.png';
        $s3Key = 'profile/' . $nama_foto;

        $this->s3->upload($s3Key, $foto, 'image/png');

        return ['filename' => $nama_foto, 'key' => $s3Key];
    }

    /**
     * Delete a profile photo from S3.
     */
    public function deleteProfilePhoto(string $filename): bool
    {
        return $this->s3->delete('profile/' . $filename);
    }

    /**
     * Delete a presensi photo from S3.
     */
    public function deletePresensiPhoto(string $type, string $filename): bool
    {
        return $this->s3->delete('presensi/' . $type . '/' . $filename);
    }

    /**
     * Get the public URL for a profile photo.
     */
    public function getProfilePhotoUrl(string $filename): string
    {
        return site_url('media/profile/' . $filename);
    }

    /**
     * Get the public URL for a presensi photo.
     */
    public function getPresensiPhotoUrl(string $type, string $filename): string
    {
        return site_url('media/presensi/' . $type . '/' . $filename);
    }
}
