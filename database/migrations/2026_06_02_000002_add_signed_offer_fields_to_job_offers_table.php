<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_offers', function (Blueprint $table) {
            if (!Schema::hasColumn('job_offers', 'signed_offer_path')) {
                $table->string('signed_offer_path')->nullable()->after('declined_at');
            }

            if (!Schema::hasColumn('job_offers', 'signed_offer_uploaded_at')) {
                $table->timestamp('signed_offer_uploaded_at')->nullable()->after('signed_offer_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_offers', function (Blueprint $table) {
            if (Schema::hasColumn('job_offers', 'signed_offer_uploaded_at')) {
                $table->dropColumn('signed_offer_uploaded_at');
            }

            if (Schema::hasColumn('job_offers', 'signed_offer_path')) {
                $table->dropColumn('signed_offer_path');
            }
        });
    }
};
