<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transmittals') || ! Schema::hasTable('users')) {
            throw new RuntimeException('History parent tables are absent.');
        }
        if (Schema::hasTable('transmittal_histories')) {
            throw new RuntimeException('transmittal_histories exists; reconcile it instead of recreating it.');
        }

        Schema::create('transmittal_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transmittal_id')->constrained('transmittals')->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('action');
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');
            $table->index(['transmittal_id', 'created_at'], 'transmittal_histories_transmittal_created_idx');
        });
    }

    public function down(): void
    {
        // Audit histories are retained on rollback.
    }
};
