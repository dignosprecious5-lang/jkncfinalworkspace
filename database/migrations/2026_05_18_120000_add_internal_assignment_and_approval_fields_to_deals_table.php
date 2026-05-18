<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('deals')) {
            return;
        }

        Schema::table('deals', function (Blueprint $table) {
            if (! Schema::hasColumn('deals', 'prepared_by')) {
                $table->string('prepared_by')->nullable()->after('associate_notes');
            }

            if (! Schema::hasColumn('deals', 'reviewed_by')) {
                $table->string('reviewed_by')->nullable()->after('prepared_by');
            }

            if (! Schema::hasColumn('deals', 'internal_name')) {
                $table->string('internal_name')->nullable()->after('reviewed_by');
            }

            if (! Schema::hasColumn('deals', 'internal_date')) {
                $table->date('internal_date')->nullable()->after('internal_name');
            }

            if (! Schema::hasColumn('deals', 'client_fullname_signature')) {
                $table->string('client_fullname_signature')->nullable()->after('internal_date');
            }

            if (! Schema::hasColumn('deals', 'referred_closed_by')) {
                $table->string('referred_closed_by')->nullable()->after('client_fullname_signature');
            }

            if (! Schema::hasColumn('deals', 'internal_sales_marketing')) {
                $table->string('internal_sales_marketing')->nullable()->after('referred_closed_by');
            }

            if (! Schema::hasColumn('deals', 'lead_consultant')) {
                $table->string('lead_consultant')->nullable()->after('internal_sales_marketing');
            }

            if (! Schema::hasColumn('deals', 'lead_associate_assigned')) {
                $table->string('lead_associate_assigned')->nullable()->after('lead_consultant');
            }

            if (! Schema::hasColumn('deals', 'internal_finance')) {
                $table->string('internal_finance')->nullable()->after('lead_associate_assigned');
            }

            if (! Schema::hasColumn('deals', 'internal_president')) {
                $table->string('internal_president')->nullable()->after('internal_finance');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('deals')) {
            return;
        }

        Schema::table('deals', function (Blueprint $table) {
            $columns = [
                'internal_president',
                'internal_finance',
                'lead_associate_assigned',
                'lead_consultant',
                'internal_sales_marketing',
                'referred_closed_by',
                'client_fullname_signature',
                'internal_date',
                'internal_name',
                'reviewed_by',
                'prepared_by',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('deals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};