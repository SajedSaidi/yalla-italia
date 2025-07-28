<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('qualifications');
        });
        Schema::create('student_qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('qualification_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['student_id', 'qualification_id']);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('qualifications')->nullable();
        });
        Schema::dropIfExists('student_qualifications');
    }
};
