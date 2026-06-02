<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('secretary_certificates', function (Blueprint $table) {
            if (!Schema::hasColumn('secretary_certificates', 'resolution_body')) {
                $table->longText('resolution_body')->nullable()->after('purpose');
            }

            if (!Schema::hasColumn('secretary_certificates', 'secretary_address')) {
                $table->string('secretary_address', 500)->nullable()->after('secretary');
            }

            if (!Schema::hasColumn('secretary_certificates', 'secretary_tin')) {
                $table->string('secretary_tin', 100)->nullable()->after('secretary_address');
            }

            if (!Schema::hasColumn('secretary_certificates', 'notarial_place')) {
                $table->string('notarial_place', 500)->nullable()->after('secretary_tin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('secretary_certificates', function (Blueprint $table) {
            foreach (['notarial_place', 'secretary_tin', 'secretary_address', 'resolution_body'] as $column) {
                if (Schema::hasColumn('secretary_certificates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
