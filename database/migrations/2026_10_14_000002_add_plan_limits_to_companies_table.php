<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // The storage ceiling a plan grants, as raw bytes to match
            // record_files.size_bytes and avoid rounding a figure that is then
            // compared against the sum of real file sizes.
            $table->unsignedBigInteger('storage_limit_bytes')->nullable()->after('record_unit_allocation');
        });

        // Both companies currently have a NULL Record Unit allocation, which the
        // interface reads as "unlimited" — so the Repository Usage card could not
        // show the "used / allowed" meter the reference presents. Seed both limits
        // from config so the numbers are not written into the migration itself and
        // stay changeable without editing history.
        $defaults = (array) config('ordo.plan_defaults', []);

        $recordUnits = (int) ($defaults['record_units'] ?? 1000);
        $storageBytes = (int) ($defaults['storage_bytes'] ?? 5368709120);

        if ($recordUnits > 0) {
            DB::table('companies')
                ->whereNull('record_unit_allocation')
                ->update(['record_unit_allocation' => $recordUnits]);
        }

        if ($storageBytes > 0) {
            DB::table('companies')
                ->whereNull('storage_limit_bytes')
                ->update(['storage_limit_bytes' => $storageBytes]);
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('storage_limit_bytes');
        });

        // The backfilled allocations are left as they are rather than nulled:
        // reverting them would put the companies back into a state this column
        // did not create, and a NULL allocation reads as "unlimited", which is a
        // billing decision rather than a schema one.
    }
};
