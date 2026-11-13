<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('transmittals', 'workflow_version')) {
            throw new \RuntimeException('The workflow_version column already exists. Review its definition and migration history before proceeding; no schema changes were made by this migration.');
        }

        Schema::table('transmittals', function (Blueprint $table) {
            $table->string('workflow_version', 2)->nullable();
            // Referenced by outbox rows to enforce exact V2 parent classification.
            $table->unique(['workflow_version', 'id'], 'transmittals_workflow_version_id_unique');
        });
    }

    public function down(): void
    {
        throw new \RuntimeException('Destructive rollback is prohibited: workflow_version and classification data must be preserved. Use a separately reviewed, data-preserving recovery procedure.');
    }
};
