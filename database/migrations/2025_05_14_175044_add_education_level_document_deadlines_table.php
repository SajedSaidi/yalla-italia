<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('document_deadlines', function (Blueprint $table) {
            $table->enum('education_level', ['single_cycle', 'bachelor', 'master', 'phd'])->default('bachelor')->after('university_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents_deadline', function (Blueprint $table) {
            $table->dropColumn('education_level');
        });
    }
};
