<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deal_payment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('deal_id')->index();
            $table->unsignedBigInteger('deal_proposal_id')->index();
            $table->unsignedBigInteger('recorded_by_id')->nullable();
            $table->uuid('request_id');
            $table->json('payload');
            $table->json('allocations');
            $table->timestamps();
            $table->unique(['deal_id', 'request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_payment_allocations');
    }
};
