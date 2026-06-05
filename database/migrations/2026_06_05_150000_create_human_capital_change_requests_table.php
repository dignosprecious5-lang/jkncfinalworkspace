<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('human_capital_change_requests', function (Blueprint $table) {
            $table->id();
            $table->string('module')->index();
            $table->string('action')->index();
            $table->string('subject_type')->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->string('subject_name')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('status')->default('Pending Approval')->index();
            $table->unsignedBigInteger('requested_by')->nullable()->index();
            $table->string('requested_by_name')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('human_capital_change_requests');
    }
};
