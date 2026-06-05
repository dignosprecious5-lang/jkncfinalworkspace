<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            if (!Schema::hasColumn('deals', 'delete_request_status')) {
                $table->string('delete_request_status')->nullable()->after('deal_status');
            }
            if (!Schema::hasColumn('deals', 'delete_requested_by')) {
                $table->unsignedBigInteger('delete_requested_by')->nullable()->after('delete_request_status');
            }
            if (!Schema::hasColumn('deals', 'delete_requested_at')) {
                $table->timestamp('delete_requested_at')->nullable()->after('delete_requested_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn([
                'delete_request_status',
                'delete_requested_by',
                'delete_requested_at',
            ]);
        });
    }
};
