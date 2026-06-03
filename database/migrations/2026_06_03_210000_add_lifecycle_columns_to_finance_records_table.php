<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_records', function (Blueprint $table) {
            if (!Schema::hasColumn('finance_records', 'relationship_status')) {
                $table->string('relationship_status')->nullable()->after('approval_status');
            }

            if (!Schema::hasColumn('finance_records', 'next_action')) {
                $table->string('next_action')->nullable()->after('relationship_status');
            }

            if (!Schema::hasColumn('finance_records', 'disbursement_status')) {
                $table->string('disbursement_status')->nullable()->after('next_action');
            }

            if (!Schema::hasColumn('finance_records', 'transaction_progress')) {
                $table->json('transaction_progress')->nullable()->after('disbursement_status');
            }
        });

        DB::table('finance_records')
            ->orderBy('id')
            ->select(['id', 'data'])
            ->chunkById(100, function ($records): void {
                foreach ($records as $record) {
                    $data = json_decode((string) $record->data, true);
                    $data = is_array($data) ? $data : [];

                    DB::table('finance_records')
                        ->where('id', $record->id)
                        ->update([
                            'relationship_status' => data_get($data, 'relationship_status'),
                            'next_action' => data_get($data, 'next_action'),
                            'disbursement_status' => data_get($data, 'disbursement_status'),
                            'transaction_progress' => ($progress = data_get($data, 'transaction_progress')) !== null
                                ? json_encode($progress, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                                : null,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('finance_records', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('finance_records', 'transaction_progress') ? 'transaction_progress' : null,
                Schema::hasColumn('finance_records', 'disbursement_status') ? 'disbursement_status' : null,
                Schema::hasColumn('finance_records', 'next_action') ? 'next_action' : null,
                Schema::hasColumn('finance_records', 'relationship_status') ? 'relationship_status' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
