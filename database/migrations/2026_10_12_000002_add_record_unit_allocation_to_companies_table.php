<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            // Null means unlimited: the prototype has no billing entitlement behind
            // it, so a company only has a ceiling once one is assigned.
            $table->unsignedInteger('record_unit_allocation')->nullable()->after('business_name');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn('record_unit_allocation');
        });
    }
};
