<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transmittal_receipts') || ! Schema::hasTable('transmittal_recipients')) {
            throw new RuntimeException('Receipt or recipient prerequisites are absent.');
        }
        $proposed = ['recipient_id', 'signed_name', 'comments', 'receipt_code', 'snapshot', 'ip_address', 'user_agent'];
        $present = array_values(array_filter($proposed, fn (string $column): bool => Schema::hasColumn('transmittal_receipts', $column)));
        if ($present !== []) {
            throw new RuntimeException('Existing V2 receipt columns require definition, uniqueness, and row reconciliation: '.implode(', ', $present));
        }

        Schema::table('transmittal_receipts', function (Blueprint $table): void {
            // Existing V1 receipt_no and its unique index are deliberately untouched.
            // MySQL permits multiple NULL values in a unique nullable index, preserving legacy receipts.
            $table->foreignId('recipient_id')->nullable()->unique('transmittal_receipts_recipient_id_unique')
                ->constrained('transmittal_recipients')->restrictOnDelete();
            $table->string('signed_name')->nullable();
            $table->text('comments')->nullable();
            $table->string('receipt_code')->nullable();
            $table->json('snapshot')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
        });
    }

    public function down(): void
    {
        // Intentionally retain receipt columns, unique constraints, and receipt data.
    }
};
