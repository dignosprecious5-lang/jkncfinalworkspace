<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transmittals') || ! Schema::hasTable('users')
            || ! Schema::hasTable('transmittal_recipients') || ! Schema::hasTable('transmittal_documents')) {
            throw new RuntimeException('Proof parent tables are absent.');
        }
        if (Schema::hasTable('proof_of_transmissions')) {
            throw new RuntimeException('proof_of_transmissions exists; reconcile it instead of recreating it.');
        }

        Schema::create('proof_of_transmissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transmittal_id')->constrained('transmittals')->restrictOnDelete();
            $table->foreignId('recipient_id')->nullable()->constrained('transmittal_recipients')->restrictOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('transmittal_documents')->restrictOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('channel');
            $table->string('status');
            $table->string('provider_message_id')->nullable();
            $table->string('transaction_reference', 40)->nullable()->index();
            $table->timestamp('transmitted_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->json('provider_metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Intentionally retain proof and delivery evidence.
    }
};
