<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_assignments', function (Blueprint $table) {

            $table->boolean('certificate_issued')->default(false)->after('status');
            $table->timestamp('certificate_issued_at')->nullable()->after('certificate_issued');
            $table->string('certificate_code')->nullable()->after('certificate_issued_at');

        });
    }

    public function down(): void
    {
        Schema::table('training_assignments', function (Blueprint $table) {

            $table->dropColumn([
                'certificate_issued',
                'certificate_issued_at',
                'certificate_code',
            ]);

        });
    }
};