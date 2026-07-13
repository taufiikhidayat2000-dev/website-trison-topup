<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * LinkQu e-wallet payments (DANA/LinkAja/ShopeePay) return a redirect URL
     * with a long encrypted payload that overflows the default VARCHAR(255)
     * account_number column. Using raw SQL because doctrine/dbal (required by
     * Schema::change()) isn't installed.
     */
    public function up(): void
    {
        // SQLite has no fixed-length VARCHAR (columns are dynamically typed),
        // so the test suite's sqlite/:memory: connection already accepts long
        // values without this ALTER - and SQLite has no MODIFY syntax anyway.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE payments MODIFY account_number TEXT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE payments MODIFY account_number VARCHAR(255) NULL');
    }
};
