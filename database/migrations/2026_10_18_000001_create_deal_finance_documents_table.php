<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('deal_finance_documents')) {
            Schema::create('deal_finance_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('deal_id')->constrained('deals')->cascadeOnDelete();
                $table->string('document_type', 32);
                $table->string('original_name');
                $table->string('path');
                $table->string('mime_type', 128);
                $table->unsignedBigInteger('size');
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['deal_id', 'document_type']);
            });
        } else {
            // Verify existing table schema compatibility
            $requiredColumns = ['id', 'deal_id', 'document_type', 'original_name', 'path', 'mime_type', 'size', 'uploaded_by'];
            foreach ($requiredColumns as $column) {
                if (! Schema::hasColumn('deal_finance_documents', $column)) {
                    throw new RuntimeException("Existing table 'deal_finance_documents' is incompatible: missing column '{$column}'.");
                }
            }
        }
    }

    public function down(): void
    {
        // Safe rollback: Never drop if legacy migration exists or if table contains pre-existing records
        $hasLegacyMigration = DB::table('migrations')->where('migration', '2026_11_13_185000_create_deal_finance_documents_table')->exists();
        $hasRecords = Schema::hasTable('deal_finance_documents') && DB::table('deal_finance_documents')->exists();

        if (! $hasLegacyMigration && ! $hasRecords) {
            Schema::dropIfExists('deal_finance_documents');
        }
    }
};
