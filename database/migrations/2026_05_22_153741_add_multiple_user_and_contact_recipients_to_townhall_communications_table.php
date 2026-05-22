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

            if (!Schema::hasColumn('townhall_communications', 'recipient_contact_ids')) {
                $table->json('recipient_contact_ids')->nullable()->after('recipient_user_ids');
            }
        });
    }

    public function down(): void
    {
        Schema::table('townhall_communications', function (Blueprint $table) {
            if (Schema::hasColumn('townhall_communications', 'recipient_contact_ids')) {
                $table->dropColumn('recipient_contact_ids');
            }

            if (Schema::hasColumn('townhall_communications', 'recipient_user_ids')) {
                $table->dropColumn('recipient_user_ids');
            }
        });
    }
};
