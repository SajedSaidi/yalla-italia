<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_language_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('language_certificate_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['student_id', 'language_certificate_id'], 'student_lang_cert_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_language_certificates');
    }
};
