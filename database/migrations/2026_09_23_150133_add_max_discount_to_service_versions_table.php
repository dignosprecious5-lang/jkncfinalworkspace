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
        Schema::table('service_versions', function (Blueprint $table) {
            if (!Schema::hasColumn('service_versions', 'max_discount')) {
                $table->decimal('max_discount', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'discount_allowed')) {
                $table->string('discount_allowed')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_versions', function (Blueprint $table) {
            $table->dropColumn(['max_discount', 'discount_allowed']);
        });
    }
};