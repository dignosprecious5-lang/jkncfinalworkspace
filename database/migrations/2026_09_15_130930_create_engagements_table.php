<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('engagements')) {
            Schema::create('engagements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('service_version_id')->nullable();
                $table->string('engagement_type')->default('regular');
                $table->string('status')->default('active');
                $table->date('start_date')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive when referenced by operational_tasks / engagement_periods.
    }
};
