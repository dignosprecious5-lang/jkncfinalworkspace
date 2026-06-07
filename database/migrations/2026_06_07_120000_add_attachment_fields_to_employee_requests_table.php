<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_requests', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('admin_note');
            }

            if (! Schema::hasColumn('employee_requests', 'attachment_original_name')) {
                $table->string('attachment_original_name')->nullable()->after('attachment_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_requests', function (Blueprint $table) {
            if (Schema::hasColumn('employee_requests', 'attachment_original_name')) {
                $table->dropColumn('attachment_original_name');
            }

            if (Schema::hasColumn('employee_requests', 'attachment_path')) {
                $table->dropColumn('attachment_path');
            }
        });
    }
};
