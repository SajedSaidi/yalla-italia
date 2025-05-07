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
        Schema::rename('academic_year_document_type', 'document_deadlines');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('document_deadlines', 'academic_year_document_type');
    }
};
