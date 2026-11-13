<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transmittals') || ! Schema::hasTable('transmittal_recipients')
            || ! Schema::hasTable('transmittal_receipts')) {
            throw new RuntimeException('Acknowledgment parent tables are absent.');
        }
        if (Schema::hasTable('external_acknowledgments')) {
            throw new RuntimeException('external_acknowledgments exists; reconcile it instead of recreating it.');
        }

        Schema::create('external_acknowledgments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transmittal_id')->constrained('transmittals')->restrictOnDelete();
            $table->foreignId('recipient_id')->constrained('transmittal_recipients')->restrictOnDelete();
            $table->foreignId('receipt_id')->nullable()->constrained('transmittal_receipts')->restrictOnDelete();
            $table->string('event');
            $table->json('details')->nullable();
            $table->timestamp('occurred_at');
            $table->index(['recipient_id', 'occurred_at'], 'external_ack_recipient_occurred_idx');
        });
    }

    public function down(): void
    {
        // External acknowledgment audit evidence is retained on rollback.
    }
};
