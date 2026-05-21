<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_history_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('description');
            $table->string('extra_label')->nullable();
            $table->string('extra_value')->nullable();
            $table->string('user_name')->nullable();
            $table->string('user_initials', 12)->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index('contact_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_history_entries');
    }
};
