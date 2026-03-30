<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE users MODIFY balance DECIMAL(14,6) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE external_clients MODIFY balance DECIMAL(14,6) NOT NULL DEFAULT 0');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE users ALTER COLUMN balance TYPE DECIMAL(14,6) USING round(balance::numeric, 6)');
            DB::statement('ALTER TABLE external_clients ALTER COLUMN balance TYPE DECIMAL(14,6) USING round(balance::numeric, 6)');

            return;
        }

        // SQLite: numeric affinity; precision enforced in application (BalanceService / bcmath).
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE users MODIFY balance DECIMAL(12,2) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE external_clients MODIFY balance DECIMAL(14,2) NOT NULL DEFAULT 0');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE users ALTER COLUMN balance TYPE DECIMAL(12,2) USING round(balance::numeric, 2)');
            DB::statement('ALTER TABLE external_clients ALTER COLUMN balance TYPE DECIMAL(14,2) USING round(balance::numeric, 2)');
        }
    }
};
