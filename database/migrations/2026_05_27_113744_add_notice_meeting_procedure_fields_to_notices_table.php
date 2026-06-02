<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            if (!Schema::hasColumn('notices', 'meeting_mode')) {
                $table->string('meeting_mode')->nullable()->after('secretary');
            }
            if (!Schema::hasColumn('notices', 'meeting_platform')) {
                $table->string('meeting_platform')->nullable()->after('meeting_mode');
            }
            if (!Schema::hasColumn('notices', 'meeting_link_details')) {
                $table->text('meeting_link_details')->nullable()->after('meeting_platform');
            }
            if (!Schema::hasColumn('notices', 'authorized_meeting_officer')) {
                $table->string('authorized_meeting_officer')->nullable()->after('meeting_link_details');
            }
            if (!Schema::hasColumn('notices', 'confirmation_email')) {
                $table->string('confirmation_email')->nullable()->after('authorized_meeting_officer');
            }
            if (!Schema::hasColumn('notices', 'confirmation_phone')) {
                $table->string('confirmation_phone')->nullable()->after('confirmation_email');
            }
            if (!Schema::hasColumn('notices', 'office_address')) {
                $table->text('office_address')->nullable()->after('confirmation_phone');
            }
            if (!Schema::hasColumn('notices', 'email_phone_confirmation_deadline')) {
                $table->string('email_phone_confirmation_deadline')->nullable()->after('office_address');
            }
            if (!Schema::hasColumn('notices', 'physical_submission_deadline')) {
                $table->string('physical_submission_deadline')->nullable()->after('email_phone_confirmation_deadline');
            }
            if (!Schema::hasColumn('notices', 'authority_calling_meeting')) {
                $table->string('authority_calling_meeting')->nullable()->after('physical_submission_deadline');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $columns = [
                'meeting_mode',
                'meeting_platform',
                'meeting_link_details',
                'authorized_meeting_officer',
                'confirmation_email',
                'confirmation_phone',
                'office_address',
                'email_phone_confirmation_deadline',
                'physical_submission_deadline',
                'authority_calling_meeting',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('notices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
