<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_offers', function (Blueprint $table) {
            if (!Schema::hasColumn('job_offers', 'accept_token')) {
                $table->string('accept_token', 100)->nullable()->unique()->after('candidate_email');
            }

            if (!Schema::hasColumn('job_offers', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('job_offers', 'declined_at')) {
                $table->timestamp('declined_at')->nullable()->after('accepted_at');
            }
        });

        DB::table('job_offers')
            ->whereNull('accept_token')
            ->orderBy('id')
            ->get()
            ->each(function ($offer) {
                do {
                    $token = Str::random(64);
                } while (DB::table('job_offers')->where('accept_token', $token)->exists());

                DB::table('job_offers')
                    ->where('id', $offer->id)
                    ->update(['accept_token' => $token]);
            });
    }

    public function down(): void
    {
        Schema::table('job_offers', function (Blueprint $table) {
            if (Schema::hasColumn('job_offers', 'declined_at')) {
                $table->dropColumn('declined_at');
            }

            if (Schema::hasColumn('job_offers', 'accepted_at')) {
                $table->dropColumn('accepted_at');
            }

            if (Schema::hasColumn('job_offers', 'accept_token')) {
                $table->dropColumn('accept_token');
            }
        });
    }
};
