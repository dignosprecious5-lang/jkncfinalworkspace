<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_audit_logs')) {
            return;
        }

        Schema::table('product_audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('product_audit_logs', 'product_id')) {
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            }
            if (! Schema::hasColumn('product_audit_logs', 'user_name')) {
                $table->string('user_name')->nullable();
            }
            if (! Schema::hasColumn('product_audit_logs', 'action')) {
                $table->string('action')->nullable();
            }
            if (! Schema::hasColumn('product_audit_logs', 'title')) {
                $table->string('title')->nullable();
            }
            if (! Schema::hasColumn('product_audit_logs', 'details')) {
                $table->text('details')->nullable();
            }
            if (! Schema::hasColumn('product_audit_logs', 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn('product_audit_logs', 'message')) {
                $table->text('message')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive to preserve product audit log history.
    }
};
