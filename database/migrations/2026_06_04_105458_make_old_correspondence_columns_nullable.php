<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function columnExists(string $column): bool
    {
        return Schema::hasColumn('correspondences', $column);
    }

    public function up(): void
    {
        if (!Schema::hasTable('correspondences')) {
            return;
        }

        /*
         * This fixes production errors from the old correspondence table schema.
         * Old required columns such as uploaded_date may still be NOT NULL without defaults,
         * while the new Town Hall-style CorrespondenceController no longer fills them.
         */
        $nullableDateColumns = [
            'uploaded_date',
            'date',
            'deadline',
        ];

        foreach ($nullableDateColumns as $column) {
            if ($this->columnExists($column)) {
                DB::statement("ALTER TABLE correspondences MODIFY {$column} DATE NULL");
            }
        }

        $nullableTimeColumns = [
            'time',
        ];

        foreach ($nullableTimeColumns as $column) {
            if ($this->columnExists($column)) {
                DB::statement("ALTER TABLE correspondences MODIFY {$column} TIME NULL");
            }
        }

        $nullableStringColumns = [
            'user',
            'tin',
            'subject',
            'sender_type',
            'sender',
            'department',
            'sent_via',
            'workflow_status',
            'approval_status',
            'review_note',
            'attachment',
        ];

        foreach ($nullableStringColumns as $column) {
            if ($this->columnExists($column)) {
                DB::statement("ALTER TABLE correspondences MODIFY {$column} VARCHAR(255) NULL");
            }
        }

        $nullableTextColumns = [
            'details',
        ];

        foreach ($nullableTextColumns as $column) {
            if ($this->columnExists($column)) {
                DB::statement("ALTER TABLE correspondences MODIFY {$column} TEXT NULL");
            }
        }

        if ($this->columnExists('submitted_by')) {
            DB::statement("ALTER TABLE correspondences MODIFY submitted_by BIGINT UNSIGNED NULL");
        }

        if ($this->columnExists('approved_by')) {
            DB::statement("ALTER TABLE correspondences MODIFY approved_by BIGINT UNSIGNED NULL");
        }

        if ($this->columnExists('approved_at')) {
            DB::statement("ALTER TABLE correspondences MODIFY approved_at TIMESTAMP NULL");
        }
    }

    public function down(): void
    {
        // Intentionally empty. Reverting to NOT NULL could break existing production data.
    }
};
