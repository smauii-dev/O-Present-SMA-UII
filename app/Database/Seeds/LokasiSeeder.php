<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class LokasiSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'nama_lokasi'       => 'SMA UII Yogyakarta',
                'slug'              => 'sma-uii-yogyakarta',
                'alamat_lokasi'     => 'Jl. Taman Siswa No.158, Wirogunan, Kec. Mergangsan, Kota Yogyakarta, Daerah Istimewa Yogyakarta 55151',
                'tipe_lokasi'       => 'Pusat',
                // -7.814091875016384, 110.37608743217014 Lokasi Presensi SMA UII
                'latitude'          => '-7.73641297411702',
                'longitude'         => '110.44313918090855',
                'radius'            => 500,
                'zona_waktu'        => 'Asia/Jakarta',
                'jam_masuk'         => '08:00:00',
                'jam_pulang'        => '15:30:00',
            ],
        ];

        // Simple Queries
        // $this->db->query('INSERT INTO lokasi_presensi (nama_lokasi, alamat_lokasi, tipe_lokasi, latitude, longitude, radius, zona_waktu, jam_masuk, jam_pulang) VALUES(:nama_lokasi:, :alamat_lokasi:, :tipe_lokasi:, :latitude:, :longitude:, :radius:, :zona_waktu:, :jam_masuk:, :jam_pulang:)', $data);

        // Using Query Builder
        $this->db->table('lokasi_presensi')->insertBatch($data);
    }
}
