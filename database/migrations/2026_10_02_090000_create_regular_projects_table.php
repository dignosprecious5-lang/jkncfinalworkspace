<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('regular_projects')) {
            Schema::create('regular_projects', function (Blueprint $table) {
                $table->id();
                $table->string('ref')->nullable()->index();
                $table->string('title')->nullable();
                $table->json('data')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('regular_projects');
    }
};
