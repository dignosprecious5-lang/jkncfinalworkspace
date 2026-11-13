<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transmittals') || ! Schema::hasTable('transmittal_documents')) {
            throw new RuntimeException('Workspace-item parent tables are absent.');
        }
        if (Schema::hasTable('transmittal_workspace_items')) {
            throw new RuntimeException('transmittal_workspace_items exists; reconcile it instead of recreating it.');
        }

        Schema::create('transmittal_workspace_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transmittal_id')->constrained('transmittals')->restrictOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('transmittal_documents')->restrictOnDelete();
            $table->unsignedInteger('position');
            $table->unsignedInteger('line_number');
            $table->string('particular')->nullable();
            $table->string('unique_id', 120)->nullable();
            $table->string('quantity', 40)->nullable();
            $table->text('description')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->index(['transmittal_id', 'position', 'id'], 'transmittal_ws_items_order_idx');
            $table->index('document_id', 'transmittal_ws_items_document_idx');
        });
    }

    public function down(): void
    {
        // Workspace rows and document associations are preserved on rollback.
    }
};
