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
                if (!Schema::hasColumn('deals', 'inquiry_records')) {
                    $table->json('inquiry_records')->nullable()->after('inquiry_details');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deals')) {
            Schema::table('deals', function (Blueprint $table) {
                if (Schema::hasColumn('deals', 'inquiry_records')) {
                    $table->dropColumn('inquiry_records');
                }
            });
        }
    }
};
