<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('townhall_communications', function (Blueprint $table) {
            if (!Schema::hasColumn('townhall_communications', 'recipient_type')) {
                $table->string('recipient_type')->default('all')->after('to_for');
            }

            if (!Schema::hasColumn('townhall_communications', 'recipient_user_id')) {
                $table->foreignId('recipient_user_id')
                    ->nullable()
                    ->after('recipient_type')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('townhall_communications', function (Blueprint $table) {
            if (Schema::hasColumn('townhall_communications', 'recipient_user_id')) {
                $table->dropConstrainedForeignId('recipient_user_id');
            }

            if (Schema::hasColumn('townhall_communications', 'recipient_type')) {
                $table->dropColumn('recipient_type');
            }
        });
    }
};
