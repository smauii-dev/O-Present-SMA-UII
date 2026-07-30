<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixPresensiNullable extends Migration
{
    public function up()
    {
        // Make tanggal_keluar and jam_keluar nullable for records where employee hasn't clocked out yet
        $this->forge->modifyColumn('presensi', [
            'tanggal_keluar' => ['type' => 'date', 'null' => true],
            'jam_keluar'     => ['type' => 'time', 'null' => true],
            'foto_keluar'    => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('presensi', [
            'tanggal_keluar' => ['type' => 'date', 'null' => false],
            'jam_keluar'     => ['type' => 'time', 'null' => false],
            'foto_keluar'    => ['type' => 'varchar', 'constraint' => 255, 'null' => false],
        ]);
    }
}