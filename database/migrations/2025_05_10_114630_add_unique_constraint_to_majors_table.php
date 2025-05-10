<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('majors', function (Blueprint $table) {
            $table->unique(['name', 'type'], 'major_name_type_unique');
        });
    }

    public function down(): void
    {
        Schema::table('majors', function (Blueprint $table) {
            $table->dropUnique('major_name_type_unique');
        });
    }
};
