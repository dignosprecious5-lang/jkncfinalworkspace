<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('townhall_acknowledgements', function (Blueprint $table) {
            if (!Schema::hasColumn('townhall_acknowledgements', 'recipient_name')) {
                $table->string('recipient_name')->nullable()->after('acknowledged_at');
            }

            if (!Schema::hasColumn('townhall_acknowledgements', 'recipient_position')) {
                $table->string('recipient_position')->nullable()->after('recipient_name');
            }

            if (!Schema::hasColumn('townhall_acknowledgements', 'recipient_department')) {
                $table->string('recipient_department')->nullable()->after('recipient_position');
            }

            if (!Schema::hasColumn('townhall_acknowledgements', 'user_account_id')) {
                $table->unsignedBigInteger('user_account_id')->nullable()->after('recipient_department');
            }

            if (!Schema::hasColumn('townhall_acknowledgements', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('user_account_id');
            }

            if (!Schema::hasColumn('townhall_acknowledgements', 'device_information')) {
                $table->string('device_information')->nullable()->after('ip_address');
            }

            if (!Schema::hasColumn('townhall_acknowledgements', 'browser_information')) {
                $table->string('browser_information')->nullable()->after('device_information');
            }

            if (!Schema::hasColumn('townhall_acknowledgements', 'operating_system')) {
                $table->string('operating_system')->nullable()->after('browser_information');
            }

            if (!Schema::hasColumn('townhall_acknowledgements', 'communication_ref_no')) {
                $table->string('communication_ref_no')->nullable()->after('operating_system');
            }

            if (!Schema::hasColumn('townhall_acknowledgements', 'session_id')) {
                $table->string('session_id')->nullable()->after('communication_ref_no');
            }

            if (!Schema::hasColumn('townhall_acknowledgements', 'acknowledgement_status')) {
                $table->string('acknowledgement_status')->nullable()->after('session_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('townhall_acknowledgements', function (Blueprint $table) {
            foreach (
                [
                    'recipient_name',
                    'recipient_position',
                    'recipient_department',
                    'user_account_id',
                    'ip_address',
                    'device_information',
                    'browser_information',
                    'operating_system',
                    'communication_ref_no',
                    'session_id',
                    'acknowledgement_status',
                ] as $column
            ) {
                if (Schema::hasColumn('townhall_acknowledgements', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
