<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('form_templates')) {
            return;
        }

        Schema::table('form_templates', function (Blueprint $table): void {
            if (! Schema::hasColumn('form_templates', 'status')) {
                $table->string('status', 40)->default('approved')->after('payload');
            }

            if (! Schema::hasColumn('form_templates', 'review_note')) {
                $table->text('review_note')->nullable()->after('status');
            }

            if (! Schema::hasColumn('form_templates', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('form_templates', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });

        DB::table('form_templates')
            ->whereNull('status')
            ->update(['status' => 'approved']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('form_templates')) {
            return;
        }

        Schema::table('form_templates', function (Blueprint $table): void {
            if (Schema::hasColumn('form_templates', 'reviewed_by')) {
                $table->dropForeign(['reviewed_by']);
                $table->dropColumn('reviewed_by');
            }

            foreach (['reviewed_at', 'review_note', 'status'] as $column) {
                if (Schema::hasColumn('form_templates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
