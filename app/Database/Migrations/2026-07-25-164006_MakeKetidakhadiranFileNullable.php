<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeKetidakhadiranFileNullable extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('ketidakhadiran') || ! $this->db->fieldExists('file', 'ketidakhadiran')) {
            return;
        }

        // Postgre: forge modifyColumn can be brittle; use raw DDL when needed
        if ($this->db->DBDriver === 'Postgre') {
            $this->db->query('ALTER TABLE ketidakhadiran ALTER COLUMN file DROP NOT NULL');
            return;
        }

        $this->forge->modifyColumn('ketidakhadiran', [
            'file' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        if (! $this->db->tableExists('ketidakhadiran') || ! $this->db->fieldExists('file', 'ketidakhadiran')) {
            return;
        }

        if ($this->db->DBDriver === 'Postgre') {
            $this->db->query('ALTER TABLE ketidakhadiran ALTER COLUMN file SET NOT NULL');
            return;
        }

        $this->forge->modifyColumn('ketidakhadiran', [
            'file' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
        ]);
    }
}
