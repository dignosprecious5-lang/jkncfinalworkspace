<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_terms', function (Blueprint $table) {
            $table->string('scope')->default('service_specific')->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('service_terms', function (Blueprint $table) {
            $table->dropColumn('scope');
        });
    }
};