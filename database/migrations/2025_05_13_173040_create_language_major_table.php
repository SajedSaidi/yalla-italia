<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('language_major', function (Blueprint $table) {
            $table->foreignId('major_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('language_id')
                ->constrained('languages')
                ->cascadeOnDelete();
            $table->primary(['major_id', 'language_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('language_major');
    }
};
