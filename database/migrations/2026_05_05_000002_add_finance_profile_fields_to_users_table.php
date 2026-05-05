<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'employee_id')) {
                $table->string('employee_id')->nullable()->after('role');
            }

            if (! Schema::hasColumn('users', 'contact_number')) {
                $table->string('contact_number')->nullable()->after('employee_id');
            }

            if (! Schema::hasColumn('users', 'position')) {
                $table->string('position')->nullable()->after('contact_number');
            }

            if (! Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable()->after('position');
            }

            if (! Schema::hasColumn('users', 'superior')) {
                $table->string('superior')->nullable()->after('department');
            }

            if (! Schema::hasColumn('users', 'superior_email')) {
                $table->string('superior_email')->nullable()->after('superior');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['superior_email', 'superior', 'department', 'position', 'contact_number', 'employee_id'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
