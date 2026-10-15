<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('terms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('terms_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('terms_templates')->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->string('scope');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms_template_items');
        Schema::dropIfExists('terms_templates');
    }
};