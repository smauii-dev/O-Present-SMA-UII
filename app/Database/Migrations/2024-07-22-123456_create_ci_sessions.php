<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCiSessions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'VARCHAR',
                'constraint' => 128,
                'null' => false,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => false,
            ],
            'timestamp' => [
                'type' => 'TIMESTAMPTZ',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
                'null' => false,
            ],
            'data' => [
                'type' => 'BYTEA',
                'null' => false,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('timestamp', false);
        // ifNotExists so re-runs / mixed environments do not blow up
        $this->forge->createTable('ci_sessions', true);
    }

    public function down()
    {
        $this->forge->dropTable('ci_sessions', true);
    }
}