<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return ! empty($indexes);
    }

    public function up(): void
    {
        Schema::table('deal_proposals', function (Blueprint $table): void {
            // 1. Create a replacement supporting index for the foreign key FIRST
            if (! $this->hasIndex('deal_proposals', 'deal_proposals_deal_id_fk_idx')) {
                $table->index('deal_id', 'deal_proposals_deal_id_fk_idx');
            }

            // 2. Drop the single-column unique index safely without touching the foreign key
            if ($this->hasIndex('deal_proposals', 'deal_proposals_deal_id_unique')) {
                $table->dropUnique('deal_proposals_deal_id_unique');
            }

            // 3. Add proposal versioning columns if not present
            if (! Schema::hasColumn('deal_proposals', 'proposal_version')) {
                $table->unsignedInteger('proposal_version')->default(1);
            }
            if (! Schema::hasColumn('deal_proposals', 'is_current')) {
                $table->boolean('is_current')->default(true);
            }
            if (! Schema::hasColumn('deal_proposals', 'revision_request_key')) {
                $table->string('revision_request_key')->nullable()->unique();
            }
            if (! Schema::hasColumn('deal_proposals', 'subject')) {
                $table->string('subject')->nullable();
            }
            if (! Schema::hasColumn('deal_proposals', 'introduction')) {
                $table->longText('introduction')->nullable();
            }
            if (! Schema::hasColumn('deal_proposals', 'scope')) {
                $table->longText('scope')->nullable();
            }
            if (! Schema::hasColumn('deal_proposals', 'terms')) {
                $table->longText('terms')->nullable();
            }
            if (! Schema::hasColumn('deal_proposals', 'discount')) {
                $table->decimal('discount', 15, 2)->nullable();
            }
            if (! Schema::hasColumn('deal_proposals', 'tax')) {
                $table->decimal('tax', 15, 2)->nullable();
            }

            // 4. Add the composite unique index (deal_id, proposal_version)
            if (! $this->hasIndex('deal_proposals', 'deal_proposals_deal_version_unique')) {
                $table->unique(['deal_id', 'proposal_version'], 'deal_proposals_deal_version_unique');
            }

            // 5. Drop the temporary supporting index since the composite unique index now covers deal_id
            if ($this->hasIndex('deal_proposals', 'deal_proposals_deal_id_fk_idx')) {
                $table->dropIndex('deal_proposals_deal_id_fk_idx');
            }
        });

        DB::table('deal_proposals')
            ->whereNull('status')
            ->update(['status' => 'Draft']);
    }

    public function down(): void
    {
        $hasMultipleVersions = DB::table('deal_proposals')
            ->select('deal_id')
            ->groupBy('deal_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasMultipleVersions) {
            throw new RuntimeException('Cannot remove proposal versioning while a deal has saved proposal revisions.');
        }

        Schema::table('deal_proposals', function (Blueprint $table): void {
            // 1. Create temporary supporting index for foreign key
            if (! $this->hasIndex('deal_proposals', 'deal_proposals_deal_id_fk_idx')) {
                $table->index('deal_id', 'deal_proposals_deal_id_fk_idx');
            }

            // 2. Drop composite unique index
            if ($this->hasIndex('deal_proposals', 'deal_proposals_deal_version_unique')) {
                $table->dropUnique('deal_proposals_deal_version_unique');
            }

            // 3. Drop revision request key unique index
            if ($this->hasIndex('deal_proposals', 'deal_proposals_revision_request_key_unique')) {
                $table->dropUnique('deal_proposals_revision_request_key_unique');
            }

            // 4. Drop versioning columns
            $colsToDrop = array_filter([
                'proposal_version',
                'is_current',
                'revision_request_key',
                'subject',
                'introduction',
                'scope',
                'terms',
                'discount',
                'tax',
            ], function ($col) {
                return Schema::hasColumn('deal_proposals', $col);
            });

            if (! empty($colsToDrop)) {
                $table->dropColumn($colsToDrop);
            }

            // 5. Restore original single-column unique index
            if (! $this->hasIndex('deal_proposals', 'deal_proposals_deal_id_unique')) {
                $table->unique('deal_id', 'deal_proposals_deal_id_unique');
            }

            // 6. Drop temporary supporting index
            if ($this->hasIndex('deal_proposals', 'deal_proposals_deal_id_fk_idx')) {
                $table->dropIndex('deal_proposals_deal_id_fk_idx');
            }
        });
    }
};
