<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            if (!Schema::hasColumn('notices', 'original_notice_path')) {
                $table->string('original_notice_path')->nullable()->after('document_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            if (Schema::hasColumn('notices', 'original_notice_path')) {
                $table->dropColumn('original_notice_path');
            }
        });
    }
};
