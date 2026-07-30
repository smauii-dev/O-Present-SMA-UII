<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PegawaiSeeder extends Seeder
{
    public function run()
    {
        // Resolve FKs by slug — never hardcode IDs (dev/prod drift)
        $lokasi = $this->db->table('lokasi_presensi')->where('slug', 'sma-uii-yogyakarta')->get()->getRow();
        if (! $lokasi) {
            $this->call('LokasiSeeder');
            $lokasi = $this->db->table('lokasi_presensi')->where('slug', 'sma-uii-yogyakarta')->get()->getRow();
        }
        if (! $lokasi) {
            echo "PegawaiSeeder: lokasi sma-uii-yogyakarta missing\n";

            return;
        }
        $lokasiId = (int) $lokasi->id;

        $jabatanBySlug = static function ($db, string $slug, string $label) {
            $row = $db->table('jabatan')->where('slug', $slug)->get()->getRow();
            if ($row) {
                return (int) $row->id;
            }
            $db->table('jabatan')->insert(['jabatan' => $label, 'slug' => $slug]);

            return (int) $db->insertID();
        };

        $data = [
            [
                'nip'                => 'PEG-0001',
                'id_jabatan'         => $jabatanBySlug($this->db, 'chief-executive-officer', 'Chief Executive Officer'),
                'id_lokasi_presensi' => $lokasiId,
                'nama'               => 'Jaya Wahyudi Putra',
                'jenis_kelamin'      => 'Laki-laki',
                'alamat'             => 'Yogyakarta',
                'no_handphone'       => '081234567891',
                'foto'               => 'default.jpg',
            ],
            [
                'nip'                => 'PEG-0002',
                'id_jabatan'         => $jabatanBySlug($this->db, 'sales-lead', 'Sales Lead'),
                'id_lokasi_presensi' => $lokasiId,
                'nama'               => 'Tamani Indah Permata',
                'jenis_kelamin'      => 'Perempuan',
                'alamat'             => 'Yogyakarta',
                'no_handphone'       => '081281010191',
                'foto'               => 'default.jpg',
            ],
            [
                'nip'                => 'PEG-0003',
                'id_jabatan'         => $jabatanBySlug($this->db, 'siswa', 'Siswa'),
                'id_lokasi_presensi' => $lokasiId,
                'nama'               => 'Ahmad Hanif',
                'jenis_kelamin'      => 'Laki-laki',
                'alamat'             => 'Yogyakarta',
                'no_handphone'       => '081287761290',
                'foto'               => 'default.jpg',
            ],
        ];

        foreach ($data as $pegawai) {
            $existing = $this->db->table('pegawai')->where('nip', $pegawai['nip'])->get()->getRow();
            if ($existing) {
                $this->db->table('pegawai')->where('id', $existing->id)->update([
                    'id_lokasi_presensi' => $lokasiId,
                    'id_jabatan'         => $pegawai['id_jabatan'],
                ]);
                echo "Updated NIP {$pegawai['nip']} → lokasi SMA UII\n";
                continue;
            }

            $this->db->table('pegawai')->insert($pegawai);
            echo "Created pegawai: {$pegawai['nip']} - {$pegawai['nama']}\n";
        }
    }
}
