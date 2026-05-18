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
            $stringColumns = [
                'prepared_by' => 'associate_notes',
                'reviewed_by' => 'prepared_by',
                'internal_name' => 'reviewed_by',
                'client_fullname_signature' => 'internal_date',
                'referred_closed_by' => 'client_fullname_signature',
                'internal_sales_marketing' => 'referred_closed_by',
                'lead_consultant' => 'internal_sales_marketing',
                'lead_associate_assigned' => 'lead_consultant',
                'internal_finance' => 'lead_associate_assigned',
                'internal_president' => 'internal_finance',
            ];

            foreach ($stringColumns as $column => $afterColumn) {
                if (! Schema::hasColumn('deals', $column)) {
                    $table->string($column)->nullable()->after($afterColumn);
                }
            }

            if (! Schema::hasColumn('deals', 'internal_date')) {
                $table->date('internal_date')->nullable()->after('internal_name');
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
                'prepared_by',
                'reviewed_by',
                'internal_name',
                'internal_date',
                'client_fullname_signature',
                'referred_closed_by',
                'internal_sales_marketing',
                'lead_consultant',
                'lead_associate_assigned',
                'internal_finance',
                'internal_president',
            ];

            $existingColumns = array_values(array_filter($columns, fn (string $column): bool => Schema::hasColumn('deals', $column)));
            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
