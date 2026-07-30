<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Myth\Auth\Password;

class SiswaSeeder extends Seeder
{
    public function run()
    {
        // Get Siswa jabatan ID
        $siswaJabatan = $this->db->table('jabatan')->where('slug', 'siswa')->get()->getRow();
        if (!$siswaJabatan) {
            $this->db->table('jabatan')->insert([
                'jabatan' => 'Siswa',
                'slug'    => 'siswa',
            ]);
            $siswaJabatanId = $this->db->insertID();
        } else {
            $siswaJabatanId = $siswaJabatan->id;
        }

        // Always bind students to canonical campus point (not first arbitrary row)
        $lokasi = $this->db->table('lokasi_presensi')->where('slug', 'sma-uii-yogyakarta')->get()->getRow();
        if (! $lokasi) {
            $this->call('LokasiSeeder');
            $lokasi = $this->db->table('lokasi_presensi')->where('slug', 'sma-uii-yogyakarta')->get()->getRow();
        }
        if (! $lokasi) {
            echo "SiswaSeeder: lokasi sma-uii-yogyakarta missing, abort.\n";

            return;
        }
        $lokasiId = (int) $lokasi->id;

        // Default password hash for "password123"
        $defaultPasswordHash = Password::hash('password123');

        // Student data from SQL dump
        // NIPs start from PEG-0018 to avoid conflicts with existing PEG-0003 to PEG-0017
        $students = [
            [
                'nip' => 'PEG-0018',
                'nama' => 'siswa14',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'X-ICT-1',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa14@gmail.com',
                'username' => 'siswa14',
                'password_hash' => '$2y$10$yKbSnDn/BJ2jwVjJcpE0U.0BuFNu6t9VN6YYtPCsUWfa.2Qv6HqGa',
            ],
            [
                'nip' => 'PEG-0019',
                'nama' => 'siswa15',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'X-ICT-2',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa15@gmail.com',
                'username' => 'siswa15',
                'password_hash' => '$2y$10$5gaEm5pbz/OMWN678HcOLO.mWFTgrzvNrqEt2PiM9hCUwfdvIJVhe',
            ],
            [
                'nip' => 'PEG-0019',
                'nama' => 'siswa16',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'X-ICT-3',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa16@gmail.com',
                'username' => 'siswa16',
                'password_hash' => '$2y$10$BkyBqlMg.sEr6k0iwABurOTO7S1dNGJN3abvW3mTEbc6YJM.lBq32',
            ],
            [
                'nip' => 'PEG-0020',
                'nama' => 'siswa17',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'X-1',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa17@gmail.com',
                'username' => 'siswa17',
                'password_hash' => '$2y$10$TZHZFeX5oIvvSuzHr4mOO.LBpIw5tUU2euk./Eq5Vx0F2aARuZLIq',
            ],
            [
                'nip' => 'PEG-0021',
                'nama' => 'siswa18',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'X-2',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa18@gmail.com',
                'username' => 'siswa18',
                'password_hash' => '$2y$10$lgSz1REgfc38Tjkie3nTt.bM55tnCLDwXxjhM91JV6O2YvCOPscwy',
            ],
            [
                'nip' => 'PEG-0021',
                'nama' => 'siswa19',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'X-3',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa19@gmail.com',
                'username' => 'siswa19',
                'password_hash' => '$2y$10$MFCSX0h3ERGtPqv4sdKL8uMjYX9iR8JTfd6jAboalGJzo7J8l7gOS',
            ],
            [
                'nip' => 'PEG-0022',
                'nama' => 'siswa20',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'X-4',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa20@gmail.com',
                'username' => 'siswa20',
                'password_hash' => '$2y$10$919xjldHxCzU62qU2f1ik.zT.VJvmxoL0ruY6bu8yT2rCvqc.RMU.',
            ],
            [
                'nip' => 'PEG-0022',
                'nama' => 'siswa21',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'X-5',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa21@gmail.com',
                'username' => 'siswa21',
                'password_hash' => '$2y$10$UK1xbawjmb7gCptoaanGUeWvZqYdrsCZ0KxJSRLMFWs6YwOSrc7wu',
            ],
            [
                'nip' => 'PEG-0023',
                'nama' => 'siswa22',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'X-ICT-1',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa22@gmail.com',
                'username' => 'siswa22',
                'password_hash' => '$2y$10$JRaQ7IILSM2qiPUAjbBrlua6AFr7m1rZrv9m31pOSlQZq/59zFY6q',
            ],
            [
                'nip' => 'PEG-0024',
                'nama' => 'siswa23',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'X-ICT-2',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa23@gmail.com',
                'username' => 'siswa23',
                'password_hash' => '$2y$10$N3lu3Pb7grvRditsA4dhCOZS1KiRITXka6LPGsYZxpb03KJ4Mr5Q6',
            ],
            [
                'nip' => 'PEG-0024',
                'nama' => 'siswa24',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'X-ICT-3',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa24@gmail.com',
                'username' => 'siswa24',
                'password_hash' => '$2y$10$aCVzbOQgccWLfl.FhIuAmOTnjuM.7vHRIzg3xNz7f8F38ZlOl/IAu',
            ],
            [
                'nip' => 'PEG-0025',
                'nama' => 'siswa25',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'X-1',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa25@gmail.com',
                'username' => 'siswa25',
                'password_hash' => '$2y$10$X3U2HrOr/zMTxpU2huSMOus1JKS3dFmGW0Ah/V.rqZSqUdLtmij0m',
            ],
            [
                'nip' => 'PEG-0025',
                'nama' => 'siswa26',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'X-2',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa26@gmail.com',
                'username' => 'siswa26',
                'password_hash' => '$2y$10$0uaU1bP9zEXcU7wGt6Jr7uB.och3yyLtXL3N14WNl5flwL8ov1PFm',
            ],
            [
                'nip' => 'PEG-0026',
                'nama' => 'siswa27',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'X-3',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa27@gmail.com',
                'username' => 'siswa27',
                'password_hash' => '$2y$10$Our46GyCDKoQI43h24seyeoI40EDPx0n5mm6cQ0H/JUgIKwq6ag4m',
            ],
            [
                'nip' => 'PEG-0027',
                'nama' => 'siswa28',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'X-4',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa28@gmail.com',
                'username' => 'siswa28',
                'password_hash' => '$2y$10$HClXOnFhvIrECdvox7ML8.tdB.mGP2nfiHc5piUfIjVXwiB.uWHwC',
            ],
            [
                'nip' => 'PEG-0028',
                'nama' => 'siswa29',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'X-5',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa29@gmail.com',
                'username' => 'siswa29',
                'password_hash' => '$2y$10$gXkfaSFTrZ2D5HzZU36Lku/X6.Gus/hAupZTIRKIUXLo9IaiOFLru',
            ],
            [
                'nip' => 'PEG-0029',
                'nama' => 'siswa30',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'X-ICT-1',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa30@gmail.com',
                'username' => 'siswa30',
                'password_hash' => '$2y$10$AiCmY/XIVXeGBlLVUWDSOOLrtnGHyUAQjIVaEarnCR2aisfyoJE1e',
            ],
            [
                'nip' => 'PEG-0030',
                'nama' => 'siswa31',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'X-ICT-2',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa31@gmail.com',
                'username' => 'siswa31',
                'password_hash' => '$2y$10$n3ydCQibXrC1F55TqO5yeOk0J93M6y5oOv7WIWTF6VOiI/CiaYqRC',
            ],
            [
                'nip' => 'PEG-0030',
                'nama' => 'siswa32',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'X-ICT-3',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa32@gmail.com',
                'username' => 'siswa32',
                'password_hash' => '$2y$10$r/XM7wUm60VAhCAjontD6uHPAOZPcgS5u5mxWG4xtLIZHXUSZy9Yq',
            ],
            [
                'nip' => 'PEG-0031',
                'nama' => 'siswa33',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'X-1',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa33@gmail.com',
                'username' => 'siswa33',
                'password_hash' => '$2y$10$pRjnQeBMiliYdcS6CPfwn.7hhgXd0h4O22dA8w3xcbZCsSOIg8C/6',
            ],
            [
                'nip' => 'PEG-0031',
                'nama' => 'siswa34',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'X-2',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa34@gmail.com',
                'username' => 'siswa34',
                'password_hash' => '$2y$10$2C7w8M1/EewaVexUjeMM5OXFZGQhFzkib.gpViQJ.0WNJu9RMStt6',
            ],
            [
                'nip' => 'PEG-0031',
                'nama' => 'siswa35',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'X-3',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa35@gmail.com',
                'username' => 'siswa35',
                'password_hash' => '$2y$10$ACUreXlGK34VthHVMpzapeM4k5nyKepPwvx0KOViK187U9gYaO/Je',
            ],
            [
                'nip' => 'PEG-0032',
                'nama' => 'siswa36',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'X-4',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa36@gmail.com',
                'username' => 'siswa36',
                'password_hash' => '$2y$10$NF5RbmdUJSg2KgvOxwCHxusnyy8.SeYbr3RjqVL/JWEEwW2Nn2cga',
            ],
            [
                'nip' => 'PEG-0032',
                'nama' => 'siswa37',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'X-5',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa37@gmail.com',
                'username' => 'siswa37',
                'password_hash' => '$2y$10$YFKPjLTjZlXowXsoWbT2qOKwyX41GjDoiRcOODskyGvrcnaeHXKgK',
            ],
            [
                'nip' => 'PEG-0033',
                'nama' => 'siswa38',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'X-ICT-1',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa38@gmail.com',
                'username' => 'siswa38',
                'password_hash' => '$2y$10$crwu3QTbF3nH04zlSXO8ee.iErKh20qRCbQ7xu91m9KvsgU4ysNh2',
            ],
            [
                'nip' => 'PEG-0033',
                'nama' => 'siswa39',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'X-ICT-2',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa39@gmail.com',
                'username' => 'siswa39',
                'password_hash' => '$2y$10$BKp5JPnX48JbgAcKIqoSluEAi8u9s.mW6elLw2XnMSCF7dexFZBfG',
            ],
            [
                'nip' => 'PEG-0033',
                'nama' => 'siswa40',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'X-ICT-3',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa40@gmail.com',
                'username' => 'siswa40',
                'password_hash' => '$2y$10$sljqa/zcX2c1CWY5UE6Ze.DVXaV4Wet5oNAQBXYEb0NfTT0ZEiz/2',
            ],
            [
                'nip' => 'PEG-0034',
                'nama' => 'siswa41',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'X-5',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa41@gmail.com',
                'username' => 'siswa41',
                'password_hash' => '$2y$10$FJE5.7FJZSHPeRkEKtNgVuijglHDq5fJ7fbQ5UF.wlm9brArAAysG',
            ],
            [
                'nip' => 'PEG-0034',
                'nama' => 'siswa42',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'X-ICT-1',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa42@gmail.com',
                'username' => 'siswa42',
                'password_hash' => '$2y$10$BKp5JPnX48JbgAcKIqoSluEAi8u9s.mW6elLw2XnMSCF7dexFZBfG',
            ],
            [
                'nip' => 'PEG-0035',
                'nama' => 'siswa43',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'X-ICT-2',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa43@gmail.com',
                'username' => 'siswa43',
                'password_hash' => '$2y$10$sljqa/zcX2c1CWY5UE6Ze.DVXaV4Wet5oNAQBXYEb0NfTT0ZEiz/2',
            ],
            [
                'nip' => 'PEG-0035',
                'nama' => 'siswa42',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'X-ICT-3',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa42@gmail.com',
                'username' => 'siswa42',
                'password_hash' => '$2y$10$YpoAr52Da7v7utcaX7HzDO5t/9SRRnRhMxN4VAjs631XblGbt8RuK',
            ],
            [
                'nip' => 'PEG-0036',
                'nama' => 'siswa43',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'X-5',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa43@gmail.com',
                'username' => 'siswa43',
                'password_hash' => '$2y$10$FJE5.7FJZSHPeRkEKtNgVuijglHDq5fJ7fbQ5UF.wlm9brArAAysG',
            ],
            [
                'nip' => 'PEG-0036',
                'nama' => 'siswa44',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'X-ICT-1',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa44@gmail.com',
                'username' => 'siswa44',
                'password_hash' => '$2y$10$BKp5JPnX48JbgAcKIqoSluEAi8u9s.mW6elLw2XnMSCF7dexFZBfG',
            ],
            [
                'nip' => 'PEG-0037',
                'nama' => 'siswa43',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'X-ICT-2',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa43@gmail.com',
                'username' => 'siswa43',
                'password_hash' => '$2y$10$sljqa/zcX2c1CWY5UE6Ze.DVXaV4Wet5oNAQBXYEb0NfTT0ZEiz/2',
            ],
            [
                'nip' => 'PEG-0037',
                'nama' => 'siswa44',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'X-ICT-3',
                'no_handphone' => '-',
                'foto' => 'default.jpg',
                'email' => 'siswa44@gmail.com',
                'username' => 'siswa44',
                'password_hash' => '$2y$10$YpoAr52Da7v7utcaX7HzDO5t/9SRRnRhMxN4VAjs631XblGbt8RuK',
            ],
        ];

        $pegawaiData = [];
        $userData = [];
        $authGroupsUsersData = [];

        foreach ($students as $index => $student) {
            // Skip if NIP already exists
            $existingPegawai = $this->db->table('pegawai')->where('nip', $student['nip'])->get()->getRow();
            if ($existingPegawai) {
                echo "Skipping NIP {$student['nip']} (already exists)" . PHP_EOL;
                continue;
            }

            // Skip if email already exists
            $existingUser = $this->db->table('users')->where('email', $student['email'])->get()->getRow();
            if ($existingUser) {
                echo "Skipping email {$student['email']} (already exists)" . PHP_EOL;
                continue;
            }

            // Insert pegawai
            $pegawai = [
                'nip' => $student['nip'],
                'id_jabatan' => $siswaJabatanId,
                'id_lokasi_presensi' => $lokasiId,
                'nama' => $student['nama'],
                'jenis_kelamin' => $student['jenis_kelamin'],
                'alamat' => $student['alamat'],
                'no_handphone' => $student['no_handphone'],
                'foto' => $student['foto'],
            ];
            
            $this->db->table('pegawai')->insert($pegawai);
            $pegawaiId = $this->db->insertID();

            // Insert user
            $user = [
                'id_pegawai' => $pegawaiId,
                'email' => $student['email'],
                'username' => $student['username'],
                'password_hash' => $student['password_hash'],
                'active' => 1,
                'force_pass_reset' => 1,
            ];
            
            $this->db->table('users')->insert($user);
            $userId = $this->db->insertID();

            // Assign to pegawai group (group_id = 3)
            $authGroupsUsersData[] = [
                'group_id' => 3, // pegawai group
                'user_id' => $userId,
            ];
        }

        // Batch insert auth_groups_users
        if (!empty($authGroupsUsersData)) {
            $this->db->table('auth_groups_users')->insertBatch($authGroupsUsersData);
        }

        echo "SiswaSeeder completed: " . count($students) . " students created" . PHP_EOL;
    }
}