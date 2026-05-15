<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->unique()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('employees', 'personal_email')) {
                $table->string('personal_email')->nullable()->after('email');
            }

            if (! Schema::hasColumn('employees', 'work_email')) {
                $table->string('work_email')->nullable()->unique()->after('personal_email');
            }
        });

        DB::table('employees')
            ->whereNull('personal_email')
            ->update(['personal_email' => DB::raw('email')]);

        DB::table('employees')
            ->whereNull('work_email')
            ->update(['work_email' => DB::raw('email')]);

        Schema::table('contacts', function (Blueprint $table) {
            if (! Schema::hasColumn('contacts', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->unique()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }

            if (Schema::hasColumn('employees', 'work_email')) {
                $table->dropColumn('work_email');
            }

            if (Schema::hasColumn('employees', 'personal_email')) {
                $table->dropColumn('personal_email');
            }
        });
    }
};
