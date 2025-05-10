<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing enum constraint
        DB::statement("ALTER TABLE majors MODIFY COLUMN type ENUM('single_cycle', 'bachelor', 'master', 'phd')");
    }

    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE majors MODIFY COLUMN type ENUM('bachelor', 'master', 'phd')");
    }
};
