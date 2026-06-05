<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('correspondences', function (Blueprint $table) {
            if (!Schema::hasColumn('correspondences', 'management_signature_name')) {
                $table->string('management_signature_name')->nullable()->after('management_approver_name');
            }

            if (!Schema::hasColumn('correspondences', 'management_signature_position')) {
                $table->string('management_signature_position')->nullable()->after('management_signature_name');
            }

            if (!Schema::hasColumn('correspondences', 'management_signature_department')) {
                $table->string('management_signature_department')->nullable()->after('management_signature_position');
            }

            if (!Schema::hasColumn('correspondences', 'executive_signature_name')) {
                $table->string('executive_signature_name')->nullable()->after('executive_approver_name');
            }

            if (!Schema::hasColumn('correspondences', 'executive_signature_position')) {
                $table->string('executive_signature_position')->nullable()->after('executive_signature_name');
            }

            if (!Schema::hasColumn('correspondences', 'executive_signature_department')) {
                $table->string('executive_signature_department')->nullable()->after('executive_signature_position');
            }
        });
    }

    public function down(): void
    {
        Schema::table('correspondences', function (Blueprint $table) {
            foreach ([
                'management_signature_name',
                'management_signature_position',
                'management_signature_department',
                'executive_signature_name',
                'executive_signature_position',
                'executive_signature_department',
            ] as $column) {
                if (Schema::hasColumn('correspondences', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
