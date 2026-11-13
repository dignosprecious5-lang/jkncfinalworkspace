<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transmittals') || ! Schema::hasTable('users')) {
            throw new RuntimeException('V2 prerequisite tables are absent; reconcile before applying this candidate.');
        }

        $proposed = [
            'tracking_number', 'external_reference', 'subject', 'purpose', 'direction',
            'origin_office', 'recipient_address', 'related_company', 'delivery_method',
            'delivery_actions', 'status', 'prepared_by', 'rejected_by', 'rejected_at',
            'rejection_reason', 'cancelled_by', 'cancelled_from_status', 'cancelled_at',
            'cancellation_reason', 'operations_manager', 'processing_company', 'confidentiality',
            'approved_snapshot', 'approval_authority_code', 'edit_token_hash',
        ];
        $present = array_values(array_filter($proposed, fn (string $column): bool => Schema::hasColumn('transmittals', $column)));
        if ($present !== []) {
            throw new RuntimeException('Existing transmittal V2 columns require definition and data reconciliation: '.implode(', ', $present));
        }

        Schema::table('transmittals', function (Blueprint $table): void {
            $table->string('tracking_number')->nullable();
            $table->string('external_reference')->nullable();
            $table->string('subject')->nullable();
            $table->text('purpose')->nullable();
            $table->string('direction')->nullable();
            $table->string('origin_office')->nullable();
            $table->text('recipient_address')->nullable();
            $table->string('related_company')->nullable();
            $table->string('delivery_method')->nullable();
            $table->json('delivery_actions')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('cancelled_from_status')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->string('operations_manager', 160)->nullable();
            $table->string('processing_company')->nullable();
            $table->string('confidentiality')->nullable();
            $table->json('approved_snapshot')->nullable();
            $table->string('approval_authority_code')->nullable();
            $table->string('edit_token_hash', 64)->nullable();
        });
    }

    public function down(): void
    {
        // Intentionally retain additive columns and any V2 values on rollback.
    }
};
