<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('deals', 'client_kyc_requirements')) {
            Schema::table('deals', function (Blueprint $table): void {
                $table->json('client_kyc_requirements')->nullable()->after('start_batches');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('deals', 'client_kyc_requirements')) {
            Schema::table('deals', function (Blueprint $table): void {
                $table->dropColumn('client_kyc_requirements');
            });
        }
    }
};
