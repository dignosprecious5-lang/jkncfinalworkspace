<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_scope_items', function (Blueprint $table) {
            $table->id();

            // Link this Scope of Work item to the Deal
            $table->foreignId('deal_id')
                ->constrained('deals')
                ->cascadeOnDelete();

            // Scope classification
            $table->enum('scope_type', [
                'within_scope',
                'out_of_scope',
            ])->default('within_scope');

            // Scope of Work fields
            $table->text('main_task')->nullable();
            $table->text('sub_task')->nullable();
            $table->string('responsible')->nullable();

            $table->unsignedInteger('duration')->nullable();

            $table->string('status')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_scope_items');
    }
};