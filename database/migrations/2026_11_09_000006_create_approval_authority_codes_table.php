<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transmittals') || ! Schema::hasTable('users')) {
            throw new RuntimeException('Approval authority parent tables are absent.');
        }
        if (Schema::hasTable('approval_authority_codes')) {
            throw new RuntimeException('approval_authority_codes exists; reconcile it instead of recreating it.');
        }

        Schema::create('approval_authority_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transmittal_id')->unique()->constrained('transmittals')->restrictOnDelete();
            $table->foreignId('approved_by')->constrained('users')->restrictOnDelete();
            $table->string('code')->unique();
            $table->json('frozen_snapshot');
            $table->char('snapshot_hash', 64);
            $table->timestamp('approved_at');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        // Authority records and frozen approval snapshots are retained on rollback.
    }
};
