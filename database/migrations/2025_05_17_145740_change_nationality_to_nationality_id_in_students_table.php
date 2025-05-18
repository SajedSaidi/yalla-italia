<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // 1. Add the new FK column (nullable for now)
            $table->unsignedBigInteger('nationality_id')
                ->nullable()
                ->after('address');

            // 2. Define the foreign key
            $table->foreign('nationality_id')
                ->references('id')
                ->on('nationalities')
                ->onDelete('set null');
        });

        // 3. Migrate existing nationality codes into the FK
        DB::statement(<<<'SQL'
            UPDATE students s
            JOIN nationalities n
              ON UPPER(s.nationality) = n.code
            SET s.nationality_id = n.id
        SQL);

        // 4. Drop the old string column
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('nationality');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // 1. Re-create the old string column
            $table->string('nationality', 2)->after('address')->nullable();
        });

        // 2. Copy back from FK into the string field
        DB::statement(<<<'SQL'
            UPDATE students s
            JOIN nationalities n
              ON s.nationality_id = n.id
            SET s.nationality = n.code
        SQL);

        Schema::table('students', function (Blueprint $table) {
            // 3. Drop foreign key and column
            $table->dropForeign(['nationality_id']);
            $table->dropColumn('nationality_id');
        });
    }
};
