<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bir_taxes')) {
            return;
        }

        Schema::table('bir_taxes', function (Blueprint $table) {
            if (!Schema::hasColumn('bir_taxes', 'rdo')) {
                $table->string('rdo')->nullable()->after('tax_payer');
            }

            if (!Schema::hasColumn('bir_taxes', 'tax_due')) {
                $table->decimal('tax_due', 15, 2)->nullable()->after('form_type');
            }

            if (!Schema::hasColumn('bir_taxes', 'status')) {
                $table->string('status')->nullable()->after('due_date');
            }
        });

        if (Schema::hasColumn('bir_taxes', 'registering_office') && Schema::hasColumn('bir_taxes', 'rdo')) {
            DB::table('bir_taxes')
                ->whereNull('rdo')
                ->orWhere('rdo', '')
                ->update([
                    'rdo' => DB::raw('registering_office'),
                ]);
        }

        if (Schema::hasColumn('bir_taxes', 'status')) {
            DB::table('bir_taxes')
                ->whereNull('status')
                ->update(['status' => 'Pending']);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('bir_taxes')) {
            return;
        }

        Schema::table('bir_taxes', function (Blueprint $table) {
            foreach (['rdo', 'tax_due', 'status'] as $column) {
                if (Schema::hasColumn('bir_taxes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
