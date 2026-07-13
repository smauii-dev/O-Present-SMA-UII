<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCiSessionsTable extends Migration
{
    public function up()
    {
        $this->db->query('CREATE TABLE IF NOT EXISTS ci_sessions (
            id VARCHAR(128) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            timestamp INT UNSIGNED NOT NULL DEFAULT 0,
            data TEXT NOT NULL
        )');
        $this->db->query('CREATE INDEX IF NOT EXISTS ci_sessions_timestamp ON ci_sessions (timestamp)');
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS ci_sessions');
    }
}
