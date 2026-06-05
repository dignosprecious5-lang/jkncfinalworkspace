<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('correspondences', function (Blueprint $table) {
            if (!Schema::hasColumn('correspondences', 'prepared_by_name')) {
                $table->string('prepared_by_name')->nullable()->after('from_name');
            }

            if (!Schema::hasColumn('correspondences', 'prepared_by_position')) {
                $table->string('prepared_by_position')->nullable()->after('prepared_by_name');
            }

            if (!Schema::hasColumn('correspondences', 'prepared_by_department')) {
                $table->string('prepared_by_department')->nullable()->after('prepared_by_position');
            }

            if (!Schema::hasColumn('correspondences', 'prepared_on')) {
                $table->dateTime('prepared_on')->nullable()->after('prepared_by_department');
            }

            if (!Schema::hasColumn('correspondences', 'management_approved_on')) {
                $table->dateTime('management_approved_on')->nullable()->after('management_approved_at');
            }

            if (!Schema::hasColumn('correspondences', 'executive_approved_on')) {
                $table->dateTime('executive_approved_on')->nullable()->after('executive_approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('correspondences', function (Blueprint $table) {
            foreach ([
                'prepared_by_name',
                'prepared_by_position',
                'prepared_by_department',
                'prepared_on',
                'management_approved_on',
                'executive_approved_on',
            ] as $column) {
                if (Schema::hasColumn('correspondences', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
