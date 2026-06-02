<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directors_officers', function (Blueprint $table) {
            if (!Schema::hasColumn('directors_officers', 'email')) {
                $table->string('email')->nullable()->after('officer_name');
            }
        });

        Schema::table('stockholders', function (Blueprint $table) {
            if (!Schema::hasColumn('stockholders', 'email')) {
                $table->string('email')->nullable()->after('stockholder_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('directors_officers', function (Blueprint $table) {
            if (Schema::hasColumn('directors_officers', 'email')) {
                $table->dropColumn('email');
            }
        });
        Schema::table('stockholders', function (Blueprint $table) {
            if (Schema::hasColumn('stockholders', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
