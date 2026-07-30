<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuthTables extends Migration
{
    public function up()
    {
        // Users
        $this->forge->addField([
            'id'               => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'id_pegawai'       => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'email'            => ['type' => 'varchar', 'constraint' => 255],
            'username'         => ['type' => 'varchar', 'constraint' => 30, 'null' => true],
            'password_hash'    => ['type' => 'varchar', 'constraint' => 255],
            'reset_hash'       => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'reset_at'         => ['type' => 'datetime', 'null' => true],
            'reset_expires'    => ['type' => 'datetime', 'null' => true],
            'activate_hash'    => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'status'           => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'status_message'   => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'active'           => ['type' => 'tinyint', 'constraint' => 1, 'null' => 0, 'default' => 0],
            'force_pass_reset' => ['type' => 'tinyint', 'constraint' => 1, 'null' => 0, 'default' => 0],
            'created_at'       => ['type' => 'datetime', 'null' => true],
            'updated_at'       => ['type' => 'datetime', 'null' => true],
            'deleted_at'       => ['type' => 'datetime', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->addUniqueKey('username');
        // Only add FK when creating the table fresh (column present in field list).
        // If Myth\Auth already created `users` without id_pegawai, createTable is a no-op
        // and ensureUsersPegawaiColumn() below repairs the schema.
        if (! $this->db->tableExists('users')) {
            $this->forge->addForeignKey('id_pegawai', 'pegawai', 'id', '', 'CASCADE');
        }

        $this->forge->createTable('users', true);
        $this->ensureUsersPegawaiColumn();

        // Auth Login Attempts
        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'ip_address' => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'email'      => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'user_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true], // Only for successful logins
            'date'       => ['type' => 'datetime'],
            'success'    => ['type' => 'tinyint', 'constraint' => 1],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('email');
        $this->forge->addKey('user_id');
        // NOTE: Do NOT delete the user_id or email when the user is deleted for security audits
        $this->forge->createTable('auth_logins', true);

        /*
         * Auth Tokens
         * @see https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence
         */
        $this->forge->addField([
            'id'              => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'selector'        => ['type' => 'varchar', 'constraint' => 255],
            'hashedValidator' => ['type' => 'varchar', 'constraint' => 255],
            'user_id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'expires'         => ['type' => 'datetime'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('selector');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('auth_tokens', true);

        // Password Reset Table
        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'email'      => ['type' => 'varchar', 'constraint' => 255],
            'ip_address' => ['type' => 'varchar', 'constraint' => 255],
            'user_agent' => ['type' => 'varchar', 'constraint' => 255],
            'token'      => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'datetime', 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('auth_reset_attempts', true);

        // Activation Attempts Table
        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'ip_address' => ['type' => 'varchar', 'constraint' => 255],
            'user_agent' => ['type' => 'varchar', 'constraint' => 255],
            'token'      => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'datetime', 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('auth_activation_attempts', true);

        // Groups Table
        $fields = [
            'id'          => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'varchar', 'constraint' => 255],
            'description' => ['type' => 'varchar', 'constraint' => 255],
        ];

        $this->forge->addField($fields);
        $this->forge->addKey('id', true);
        $this->forge->createTable('auth_groups', true);

        // Permissions Table
        $fields = [
            'id'          => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'varchar', 'constraint' => 255],
            'description' => ['type' => 'varchar', 'constraint' => 255],
        ];

        $this->forge->addField($fields);
        $this->forge->addKey('id', true);
        $this->forge->createTable('auth_permissions', true);

        // Groups/Permissions Table
        $fields = [
            'group_id'      => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'permission_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
        ];

        $this->forge->addField($fields);
        $this->forge->addKey(['group_id', 'permission_id']);
        $this->forge->addForeignKey('group_id', 'auth_groups', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('permission_id', 'auth_permissions', 'id', '', 'CASCADE');
        $this->forge->createTable('auth_groups_permissions', true);

        // Users/Groups Table
        $fields = [
            'group_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'user_id'  => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
        ];

        $this->forge->addField($fields);
        $this->forge->addKey(['group_id', 'user_id']);
        $this->forge->addForeignKey('group_id', 'auth_groups', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('auth_groups_users', true);

        // Users/Permissions Table
        $fields = [
            'user_id'       => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'permission_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
        ];

        $this->forge->addField($fields);
        $this->forge->addKey(['user_id', 'permission_id']);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('permission_id', 'auth_permissions', 'id', '', 'CASCADE');
        $this->forge->createTable('auth_users_permissions', true);
    }

    //--------------------------------------------------------------------

    public function down()
    {
        // drop constraints first to prevent errors
        if ($this->db->DBDriver !== 'SQLite3') { // @phpstan-ignore-line
            $this->forge->dropForeignKey('users', 'users_id_pegawai_foreign');
            $this->forge->dropForeignKey('auth_tokens', 'auth_tokens_user_id_foreign');
            $this->forge->dropForeignKey('auth_groups_permissions', 'auth_groups_permissions_group_id_foreign');
            $this->forge->dropForeignKey('auth_groups_permissions', 'auth_groups_permissions_permission_id_foreign');
            $this->forge->dropForeignKey('auth_groups_users', 'auth_groups_users_group_id_foreign');
            $this->forge->dropForeignKey('auth_groups_users', 'auth_groups_users_user_id_foreign');
            $this->forge->dropForeignKey('auth_users_permissions', 'auth_users_permissions_user_id_foreign');
            $this->forge->dropForeignKey('auth_users_permissions', 'auth_users_permissions_permission_id_foreign');
        }

        $this->forge->dropTable('users', true);
        $this->forge->dropTable('auth_logins', true);
        $this->forge->dropTable('auth_tokens', true);
        $this->forge->dropTable('auth_reset_attempts', true);
        $this->forge->dropTable('auth_activation_attempts', true);
        $this->forge->dropTable('auth_groups', true);
        $this->forge->dropTable('auth_permissions', true);
        $this->forge->dropTable('auth_groups_permissions', true);
        $this->forge->dropTable('auth_groups_users', true);
        $this->forge->dropTable('auth_users_permissions', true);
    }

    /**
     * Myth\Auth may create `users` first (without id_pegawai). App requires
     * users.id_pegawai → pegawai.id. Repair safely on every environment.
     */
    private function ensureUsersPegawaiColumn(): void
    {
        if (! $this->db->tableExists('users')) {
            return;
        }

        if (! $this->db->fieldExists('id_pegawai', 'users')) {
            $this->forge->addColumn('users', [
                'id_pegawai' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
            ]);
        }

        if (! $this->db->tableExists('pegawai') || ! $this->db->fieldExists('id_pegawai', 'users')) {
            return;
        }

        // Avoid duplicate FK on re-run / mixed Myth+App installs
        $hasFk = false;
        try {
            $row = $this->db->query(
                "SELECT 1 AS ok
                 FROM information_schema.table_constraints
                 WHERE table_schema = current_schema()
                   AND table_name = 'users'
                   AND constraint_type = 'FOREIGN KEY'
                   AND constraint_name = 'users_id_pegawai_foreign'
                 LIMIT 1"
            )->getRow();
            $hasFk = (bool) $row;
        } catch (\Throwable $e) {
            $hasFk = false;
        }

        if (! $hasFk) {
            try {
                $this->db->query(
                    'ALTER TABLE users
                     ADD CONSTRAINT users_id_pegawai_foreign
                     FOREIGN KEY (id_pegawai) REFERENCES pegawai(id)
                     ON UPDATE CASCADE ON DELETE CASCADE'
                );
            } catch (\Throwable $e) {
                // Constraint may already exist under another name — non-fatal
                log_message('debug', 'users_id_pegawai_foreign: ' . $e->getMessage());
            }
        }
    }
}
