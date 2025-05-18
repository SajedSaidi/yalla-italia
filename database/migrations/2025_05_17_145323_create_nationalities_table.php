<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nationalities', function (Blueprint $table) {
            $table->id();
            $table->char('code', 2)->unique()->index();
            $table->string('name', 255)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nationalities');
    }
};
