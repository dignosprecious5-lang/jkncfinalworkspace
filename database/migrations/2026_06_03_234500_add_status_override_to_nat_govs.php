<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('nat_govs')) {
            return;
        }

        Schema::table('nat_govs', function (Blueprint $table) {
            if (!Schema::hasColumn('nat_govs', 'status_override')) {
                $table->string('status_override')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('nat_govs')) {
            return;
        }

        Schema::table('nat_govs', function (Blueprint $table) {
            if (Schema::hasColumn('nat_govs', 'status_override')) {
                $table->dropColumn('status_override');
            }
        });
    }
};
