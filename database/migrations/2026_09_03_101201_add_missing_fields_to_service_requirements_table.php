<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requirements', function (Blueprint $table) {
            if (!Schema::hasColumn('service_requirements', 'conditional_rule')) {
                $table->text('conditional_rule')->nullable();
            }
            if (!Schema::hasColumn('service_requirements', 'validity_expiration')) {
                $table->string('validity_expiration')->nullable();
            }
            if (!Schema::hasColumn('service_requirements', 'template_link')) {
                $table->string('template_link')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_requirements', function (Blueprint $table) {
            $table->dropColumn(['conditional_rule', 'validity_expiration', 'template_link']);
        });
    }
};