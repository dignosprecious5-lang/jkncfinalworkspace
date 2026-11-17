<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('deals', 'inquiry_request_key')) {
            throw new RuntimeException('Unexpected existing inquiry idempotency column; inspect schema before continuing.');
        }

        Schema::table('deals', function (Blueprint $table): void {
            $table->uuid('inquiry_request_key')->nullable()->unique('deals_inquiry_request_key_unique');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Forward-only migration: Inquiry request keys must not be removed.');
    }
};
