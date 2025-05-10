<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_deadlines', function (Blueprint $table) {
            $table->unique(
                ['academic_year_id', 'document_type_id', 'university_id'],
                'document_deadlines_unique_triplet'
            );
        });
    }

    public function down(): void
    {
        Schema::table('document_deadlines', function (Blueprint $table) {
            $table->dropUnique('document_deadlines_unique_triplet');
        });
    }
};
