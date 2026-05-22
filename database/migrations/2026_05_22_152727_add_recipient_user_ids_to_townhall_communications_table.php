<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('townhall_communications', function (Blueprint $table) {
            if (!Schema::hasColumn('townhall_communications', 'recipient_user_ids')) {
                $table->json('recipient_user_ids')->nullable()->after('recipient_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('townhall_communications', function (Blueprint $table) {
            if (Schema::hasColumn('townhall_communications', 'recipient_user_ids')) {
                $table->dropColumn('recipient_user_ids');
            }
        });
    }
};
