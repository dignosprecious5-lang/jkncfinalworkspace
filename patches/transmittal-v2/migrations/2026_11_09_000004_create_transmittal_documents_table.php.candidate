<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transmittals') || ! Schema::hasTable('users')) {
            throw new RuntimeException('Document parent tables are absent.');
        }
        if (Schema::hasTable('transmittal_documents')) {
            throw new RuntimeException('transmittal_documents exists; reconcile it instead of recreating it.');
        }

        Schema::create('transmittal_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transmittal_id')->constrained('transmittals')->restrictOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('category');
            $table->string('original_name');
            $table->string('storage_path')->unique();
            $table->string('mime_type', 127);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->longText('ocr_text')->nullable();
            $table->json('extracted_data')->nullable();
            $table->timestamps();
            $table->index(['transmittal_id', 'category'], 'transmittal_documents_transmittal_category_idx');
        });
    }

    public function down(): void
    {
        // Intentionally retain document metadata; file reconciliation is separate and non-destructive.
    }
};
