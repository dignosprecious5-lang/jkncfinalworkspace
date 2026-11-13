<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transmittals')) {
            throw new RuntimeException('The transmittals parent table is absent.');
        }
        if (Schema::hasTable('transmittal_recipients')) {
            throw new RuntimeException('transmittal_recipients exists; reconcile its definition and ledger instead of recreating it.');
        }

        Schema::create('transmittal_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transmittal_id')->constrained('transmittals')->restrictOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('organization')->nullable();
            $table->string('acknowledgment_token_hash', 64)->nullable()->unique();
            $table->timestamp('acknowledgment_expires_at')->nullable();
            $table->timestamp('acknowledgment_revoked_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['transmittal_id', 'email'], 'transmittal_recipients_transmittal_email_idx');
            // Supports the dispatch composite FK that proves recipient ownership.
            $table->unique(['id', 'transmittal_id'], 'transmittal_recipients_id_parent_unique');
        });
    }

    public function down(): void
    {
        // Intentionally do not drop recipient or acknowledgment records.
    }
};
