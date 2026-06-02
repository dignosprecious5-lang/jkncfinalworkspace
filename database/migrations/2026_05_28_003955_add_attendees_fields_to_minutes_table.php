<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('minutes', function (Blueprint $table) {
            if (!Schema::hasColumn('minutes', 'directors_present')) {
                $table->longText('directors_present')->nullable()->after('secretary');
            }

            if (!Schema::hasColumn('minutes', 'directors_absent')) {
                $table->longText('directors_absent')->nullable()->after('directors_present');
            }

            if (!Schema::hasColumn('minutes', 'secretariat')) {
                $table->longText('secretariat')->nullable()->after('directors_absent');
            }

            if (!Schema::hasColumn('minutes', 'guests')) {
                $table->longText('guests')->nullable()->after('secretariat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('minutes', function (Blueprint $table) {
            $columns = [];

            foreach (['directors_present', 'directors_absent', 'secretariat', 'guests'] as $column) {
                if (Schema::hasColumn('minutes', $column)) {
                    $columns[] = $column;
                }
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};