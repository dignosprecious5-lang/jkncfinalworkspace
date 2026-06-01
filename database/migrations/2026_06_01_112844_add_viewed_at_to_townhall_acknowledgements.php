<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('townhall_acknowledgements', function (Blueprint $table) {
            if (!Schema::hasColumn('townhall_acknowledgements', 'viewed_at')) {
                $table->timestamp('viewed_at')->nullable()->after('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('townhall_acknowledgements', function (Blueprint $table) {
            if (Schema::hasColumn('townhall_acknowledgements', 'viewed_at')) {
                $table->dropColumn('viewed_at');
            }
        });
    }
};
