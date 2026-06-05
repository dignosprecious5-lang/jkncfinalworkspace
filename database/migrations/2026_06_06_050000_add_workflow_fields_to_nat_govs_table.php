<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nat_govs')) {
            return;
        }

        Schema::table('nat_govs', function (Blueprint $table) {
            if (! Schema::hasColumn('nat_govs', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('nat_govs', 'company_name')) {
                $table->string('company_name')->nullable()->after('company_id');
            }

            if (! Schema::hasColumn('nat_govs', 'user')) {
                $table->string('user')->nullable()->after('status_override');
            }

            if (! Schema::hasColumn('nat_govs', 'document_name')) {
                $table->string('document_name')->nullable()->after('document_path');
            }

            if (! Schema::hasColumn('nat_govs', 'date_uploaded_at')) {
                $table->dateTime('date_uploaded_at')->nullable()->after('date_uploaded');
            }

            if (! Schema::hasColumn('nat_govs', 'last_updated_by')) {
                $table->string('last_updated_by')->nullable()->after('date_uploaded_at');
            }

            if (! Schema::hasColumn('nat_govs', 'last_updated_at')) {
                $table->dateTime('last_updated_at')->nullable()->after('last_updated_by');
            }

            if (! Schema::hasColumn('nat_govs', 'workflow_status')) {
                $table->string('workflow_status')->nullable()->after('last_updated_at');
            }

            if (! Schema::hasColumn('nat_govs', 'approval_status')) {
                $table->string('approval_status')->nullable()->after('workflow_status');
            }

            if (! Schema::hasColumn('nat_govs', 'submitted_by')) {
                $table->unsignedBigInteger('submitted_by')->nullable()->after('approval_status');
            }

            if (! Schema::hasColumn('nat_govs', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('submitted_by');
            }

            if (! Schema::hasColumn('nat_govs', 'approved_at')) {
                $table->dateTime('approved_at')->nullable()->after('approved_by');
            }

            if (! Schema::hasColumn('nat_govs', 'review_note')) {
                $table->text('review_note')->nullable()->after('approved_at');
            }
        });

        DB::table('nat_govs')
            ->select(['id', 'document_path'])
            ->whereNull('document_name')
            ->whereNotNull('document_path')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('nat_govs')
                        ->where('id', $row->id)
                        ->update([
                            'document_name' => basename((string) $row->document_path),
                        ]);
                }
            });

        DB::table('nat_govs')
            ->whereNull('company_name')
            ->update([
                'company_name' => DB::raw('client'),
            ]);

        DB::table('nat_govs')
            ->whereNull('date_uploaded_at')
            ->update([
                'date_uploaded_at' => DB::raw('COALESCE(last_updated_at, updated_at, created_at)'),
            ]);

        DB::table('nat_govs')
            ->whereNull('last_updated_by')
            ->update([
                'last_updated_by' => DB::raw('COALESCE(uploaded_by, user)'),
            ]);

        DB::table('nat_govs')
            ->whereNull('last_updated_at')
            ->update([
                'last_updated_at' => DB::raw('COALESCE(updated_at, created_at)'),
            ]);

        DB::table('nat_govs')
            ->whereNull('workflow_status')
            ->update([
                'workflow_status' => DB::raw("
                    CASE
                        WHEN approved_document_path IS NOT NULL AND approved_document_path <> '' THEN 'Accepted'
                        WHEN document_path IS NOT NULL AND document_path <> '' THEN 'Submitted'
                        ELSE 'Uploaded'
                    END
                "),
            ]);

        DB::table('nat_govs')
            ->whereNull('approval_status')
            ->update([
                'approval_status' => DB::raw("
                    CASE
                        WHEN approved_document_path IS NOT NULL AND approved_document_path <> '' THEN 'Approved'
                        ELSE 'Pending'
                    END
                "),
            ]);

        DB::table('nat_govs')
            ->whereNull('approved_at')
            ->where(function ($query) {
                $query->where('workflow_status', 'Accepted')
                    ->orWhere('approval_status', 'Approved');
            })
            ->update([
                'approved_at' => DB::raw('updated_at'),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('nat_govs')) {
            return;
        }

        Schema::table('nat_govs', function (Blueprint $table) {
            foreach ([
                'company_id',
                'company_name',
                'user',
                'document_name',
                'date_uploaded_at',
                'last_updated_by',
                'last_updated_at',
                'workflow_status',
                'approval_status',
                'submitted_by',
                'approved_by',
                'approved_at',
                'review_note',
            ] as $column) {
                if (Schema::hasColumn('nat_govs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
