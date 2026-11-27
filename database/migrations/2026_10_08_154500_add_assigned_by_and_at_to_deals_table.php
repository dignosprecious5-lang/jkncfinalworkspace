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
            if (! Schema::hasColumn('deals', 'assigned_by')) {
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('deals', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            if (Schema::hasColumn('deals', 'assigned_by')) {
                $table->dropForeign(['assigned_by']);
                $table->dropColumn('assigned_by');
            }
            if (Schema::hasColumn('deals', 'assigned_at')) {
                $table->dropColumn('assigned_at');
            }
        });
    }
};
