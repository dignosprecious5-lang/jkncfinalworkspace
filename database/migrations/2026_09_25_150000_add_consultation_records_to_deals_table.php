<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deals')) {
            Schema::table('deals', function (Blueprint $table) {
                if (!Schema::hasColumn('deals', 'consultation_records')) {
                    $table->json('consultation_records')->nullable()->after('consultant_notes');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deals')) {
            Schema::table('deals', function (Blueprint $table) {
                if (Schema::hasColumn('deals', 'consultation_records')) {
                    $table->dropColumn('consultation_records');
                }
            });
        }
    }
};
