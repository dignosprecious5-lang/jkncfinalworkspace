<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_requests', 'request_type_other')) {
                $table->string('request_type_other')->nullable()->after('request_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_requests', function (Blueprint $table) {
            if (Schema::hasColumn('employee_requests', 'request_type_other')) {
                $table->dropColumn('request_type_other');
            }
        });
    }
};
