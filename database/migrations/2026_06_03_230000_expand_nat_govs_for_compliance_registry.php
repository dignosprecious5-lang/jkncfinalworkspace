<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('nat_govs')) {
            return;
        }

        Schema::table('nat_govs', function (Blueprint $table) {
            if (!Schema::hasColumn('nat_govs', 'renewal_date')) {
                $table->date('renewal_date')->nullable()->after('registration_date');
            }
        });

        if (Schema::hasColumn('nat_govs', 'deadline_date') && Schema::hasColumn('nat_govs', 'renewal_date')) {
            DB::table('nat_govs')
                ->whereNull('renewal_date')
                ->update([
                    'renewal_date' => DB::raw('deadline_date'),
                ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('nat_govs')) {
            return;
        }

        Schema::table('nat_govs', function (Blueprint $table) {
            if (Schema::hasColumn('nat_govs', 'renewal_date')) {
                $table->dropColumn('renewal_date');
            }
        });
    }
};
