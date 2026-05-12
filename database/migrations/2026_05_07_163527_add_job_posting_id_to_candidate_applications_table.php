<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('candidate_applications') && !Schema::hasColumn('candidate_applications', 'job_posting_id')) {
            Schema::table('candidate_applications', function (Blueprint $table) {
                $table->foreignId('job_posting_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('job_postings')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('candidate_applications') && Schema::hasColumn('candidate_applications', 'job_posting_id')) {
            Schema::table('candidate_applications', function (Blueprint $table) {
                $table->dropConstrainedForeignId('job_posting_id');
            });
        }
    }
};
