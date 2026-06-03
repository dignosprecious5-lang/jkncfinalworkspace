<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('townhall_communications')) {
            return;
        }

        Schema::table('townhall_communications', function (Blueprint $table) {
            if (!Schema::hasColumn('townhall_communications', 'reminder_key')) {
                $table->string('reminder_key')->nullable()->after('deadline_date');
            }

            if (!Schema::hasColumn('townhall_communications', 'reminder_trigger_date')) {
                $table->date('reminder_trigger_date')->nullable()->after('reminder_key');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('townhall_communications')) {
            return;
        }

        Schema::table('townhall_communications', function (Blueprint $table) {
            foreach (['reminder_trigger_date', 'reminder_key'] as $column) {
                if (Schema::hasColumn('townhall_communications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
