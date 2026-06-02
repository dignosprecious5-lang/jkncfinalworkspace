<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notice_attendees')) {
            Schema::create('notice_attendees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('notice_id')->constrained('notices')->cascadeOnDelete();
                $table->string('name');
                $table->string('position')->nullable();
                $table->string('email')->nullable();
                $table->string('source_type')->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->boolean('is_selected')->default(true);
                $table->timestamp('sent_at')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notice_attendees');
    }
};
