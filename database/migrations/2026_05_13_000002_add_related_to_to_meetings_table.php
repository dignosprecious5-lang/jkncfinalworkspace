<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('meetings')) {
            return;
        }

        Schema::table('meetings', function (Blueprint $table) {
            if (! Schema::hasColumn('meetings', 'related_to')) {
                $table->string('related_to')->nullable()->after('attendees');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('meetings')) {
            return;
        }

        Schema::table('meetings', function (Blueprint $table) {
            if (Schema::hasColumn('meetings', 'related_to')) {
                $table->dropColumn('related_to');
            }
        });
    }
};
