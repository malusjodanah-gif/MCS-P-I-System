<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel's default sessions.user_id is bigint (foreignId).
 * MCS users use a string primary key (user_id like "admin", "po1").
 * Convert so login/session persistence works on PostgreSQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sessions')) {
            return;
        }

        // Drop any existing sessions (payload cannot map string user ids into bigint)
        DB::table('sessions')->delete();

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            // bigint -> varchar
            DB::statement('ALTER TABLE sessions ALTER COLUMN user_id TYPE varchar(255) USING CASE WHEN user_id IS NULL THEN NULL ELSE user_id::text END');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE sessions MODIFY user_id VARCHAR(255) NULL');
        }
        // sqlite is dynamically typed; no change required
    }

    public function down(): void
    {
        // Intentionally not reverting — bigint cannot store string user IDs
    }
};
