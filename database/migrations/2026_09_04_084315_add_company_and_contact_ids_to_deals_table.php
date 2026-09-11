<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('account_id');
            $table->unsignedBigInteger('contact_id')->nullable()->after('company_id');

            $table->index('company_id');
            $table->index('contact_id');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['contact_id']);
            $table->dropColumn(['company_id', 'contact_id']);
        });
    }
};