<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('start_engagement_groups')) {
            Schema::create('start_engagement_groups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('deal_id')->nullable()->constrained('deals')->cascadeOnDelete();
                $table->string('group_name')->nullable();
                $table->string('deal_item_id')->nullable();
                $table->string('engagement_type')->nullable();
                $table->string('group_code')->nullable();
                $table->string('status')->nullable()->default('Draft');
                $table->string('project_manager')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('start_engagement_groups');
    }
};