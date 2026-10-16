<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('record_report_runs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();

            // The report and how it was taken onward, both stored as their enum
            // values rather than being inferred from the stored filters.
            $table->string('type', 30);
            $table->string('trigger', 20);

            // What produced the run, kept rather than re-derived. Re-deriving on
            // read would report the register as it stands today, which is not
            // what was presented or downloaded at the time; the metrics are
            // frozen for the same reason.
            $table->json('filters');
            $table->json('metrics');

            // The management / consultant observations written into the preview,
            // saved with the output they were written for.
            $table->text('comments')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            // History is always read newest-first within one company, so the two
            // leading columns are every query's starting point.
            $table->index(['tenant_id', 'company_id', 'created_at']);
            $table->index(['tenant_id', 'company_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_report_runs');
    }
};
