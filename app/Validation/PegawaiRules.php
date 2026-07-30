<?php

namespace App\Validation;

/**
 * Shared validation rule sets for pegawai-related API endpoints.
 * Keeps controllers thin and rules consistent (Laravel FormRequest-ish, lightweight).
 */
class PegawaiRules
{
    public static function phone(): string
    {
        return 'required|regex_match[/^(?:\+62|62|0)(?:\d{8,15})$/]';
    }

    public static function gender(): string
    {
        return 'required|in_list[Perempuan,Laki-laki]';
    }

    /**
     * Rules for creating a new pegawai + user account.
     *
     * @return array<string, string>
     */
    public static function create(): array
    {
        return [
            'nama'            => 'required|min_length[2]|max_length[255]',
            'jenis_kelamin'   => self::gender(),
            'alamat'          => 'required|min_length[3]',
            'no_handphone'    => self::phone(),
            'jabatan'         => 'required|numeric',
            'lokasi_presensi' => 'required|numeric',
            'email'           => 'required|valid_email|is_unique[users.email]',
            'username'        => 'required|alpha_numeric|min_length[5]|max_length[30]|is_unique[users.username]',
            'role'            => 'required|numeric',
        ];
    }

    /**
     * Rules for updating pegawai. Email/username uniqueness handled by caller when changed.
     *
     * @return array<string, string>
     */
    public static function update(bool $emailUnique = false, bool $usernameUnique = false): array
    {
        $rules = [
            'nama'            => 'required|min_length[2]|max_length[255]',
            'jenis_kelamin'   => self::gender(),
            'alamat'          => 'required|min_length[3]',
            'no_handphone'    => self::phone(),
            'jabatan'         => 'required|numeric',
            'lokasi_presensi' => 'required|numeric',
            'email'           => 'required|valid_email',
            'username'        => 'required|alpha_numeric|min_length[5]|max_length[30]',
            'role'            => 'required|numeric',
        ];

        if ($emailUnique) {
            $rules['email'] .= '|is_unique[users.email]';
        }
        if ($usernameUnique) {
            $rules['username'] .= '|is_unique[users.username]';
        }

        return $rules;
    }

    /**
     * Profile self-update (no jabatan/role/email changes).
     *
     * @return array<string, string>
     */
    public static function profileUpdate(): array
    {
        return [
            'nama'          => 'required|min_length[2]|max_length[255]',
            'no_handphone'  => self::phone(),
            'alamat'        => 'required|min_length[3]',
            'jenis_kelamin' => self::gender(),
        ];
    }
}
