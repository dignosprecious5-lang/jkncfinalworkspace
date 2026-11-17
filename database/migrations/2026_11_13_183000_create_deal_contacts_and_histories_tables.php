<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deal_contacts') || Schema::hasTable('deal_histories')) {
            throw new RuntimeException('Deal contact/history schema differs from the expected pre-migration state.');
        }

        Schema::create('deal_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('deal_id')->constrained('deals')->restrictOnDelete();
            $table->string('contact_type', 50)->default('Primary');
            $table->text('salutation')->nullable();
            $table->text('first_name')->nullable();
            $table->text('middle_name')->nullable();
            $table->text('last_name')->nullable();
            $table->text('name_extension')->nullable();
            $table->string('email', 191)->nullable();
            $table->text('mobile_number')->nullable();
            $table->text('address')->nullable();
            $table->text('position')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();
            $table->index('email');
        });

        Schema::create('deal_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('deal_id')->constrained('deals')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('user_name')->nullable();
            $table->string('activity_type', 50);
            $table->text('title')->nullable();
            $table->text('description');
            $table->text('old_stage')->nullable();
            $table->text('new_stage')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index('activity_type');
            $table->index(['deal_id', 'created_at'], 'deal_histories_deal_created_index');
            $table->index(['deal_id', 'activity_type'], 'deal_histories_deal_activity_index');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Forward-only migration: Deal contacts and audit history must not be dropped.');
    }
};
