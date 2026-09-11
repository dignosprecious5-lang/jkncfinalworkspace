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
        Schema::create('engagement_assignments', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | ENGAGEMENT GROUP
            |--------------------------------------------------------------------------
            */

            $table->foreignId('start_engagement_group_id')
                ->constrained('start_engagement_groups')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | ASSIGNMENT
            |--------------------------------------------------------------------------
            |
            | Examples:
            | - Project Manager
            | - Lead Consultant
            | - Lead Associate
            | - Consultant
            | - Associate
            | - Other Support
            |
            */

            $table->string('role');

            /*
            |--------------------------------------------------------------------------
            | ASSIGNED PERSON
            |--------------------------------------------------------------------------
            |
            | Kept as a name for now because the current project does not yet
            | have a confirmed staff/master-user relationship for assignments.
            |
            */

            $table->string('assigned_to');

            /*
            |--------------------------------------------------------------------------
            | ASSIGNMENT STATUS
            |--------------------------------------------------------------------------
            */

            $table->string('status')->default('Assigned');

            /*
            |--------------------------------------------------------------------------
            | ASSIGNMENT NOTES
            |--------------------------------------------------------------------------
            */

            $table->text('notes')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | INDEXES
            |--------------------------------------------------------------------------
            */

            $table->index('role');
            $table->index('assigned_to');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('engagement_assignments');
    }
};