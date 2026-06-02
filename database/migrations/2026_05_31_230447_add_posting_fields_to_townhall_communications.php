<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('townhall_communications', function (Blueprint $table) {
            if (!Schema::hasColumn('townhall_communications', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('submitted_at');
            }

            if (!Schema::hasColumn('townhall_communications', 'posted_by')) {
                $table->unsignedBigInteger('posted_by')->nullable()->after('posted_at');
            }

            if (!Schema::hasColumn('townhall_communications', 'recipient_notified_at')) {
                $table->timestamp('recipient_notified_at')->nullable()->after('posted_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('townhall_communications', function (Blueprint $table) {
            foreach (['posted_at', 'posted_by', 'recipient_notified_at'] as $column) {
                if (Schema::hasColumn('townhall_communications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
