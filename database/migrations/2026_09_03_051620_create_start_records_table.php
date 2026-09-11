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
        Schema::create('start_records', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | SOURCE RECORD
            |--------------------------------------------------------------------------
            */

            $table->foreignId('deal_id')
                ->constrained('deals')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | OPTIONAL FUTURE REFERENCES
            |--------------------------------------------------------------------------
            |
            | These are kept nullable because the current project does not yet
            | have the complete CASA / proposal-version structure from V2.2.
            |
            */

            $table->unsignedBigInteger('casa_id')->nullable();
            $table->unsignedBigInteger('proposal_id')->nullable();
            $table->unsignedBigInteger('proposal_version_id')->nullable();

            /*
            |--------------------------------------------------------------------------
            | START STATUS
            |--------------------------------------------------------------------------
            |
            | Draft
            | Structuring
            | Team Confirmation
            | Partially Acknowledged
            | Needs Reassignment
            | Team Confirmed
            | Final Review
            | Activated
            | Returned
            | Cancelled
            |
            */

            $table->string('status')->default('Draft');

            /*
            |--------------------------------------------------------------------------
            | SERVICE MEMO
            |--------------------------------------------------------------------------
            |
            | Draft
            | Ready
            | Issued
            | Amended
            |
            */

            $table->string('memo_status')->nullable();

            /*
            |--------------------------------------------------------------------------
            | ACTIVATION SUMMARY
            |--------------------------------------------------------------------------
            |
            | Stored as JSON so one START can contain multiple
            | Regular and Project engagement groups.
            |
            */

            $table->json('activation_summary')->nullable();

            /*
            |--------------------------------------------------------------------------
            | REVIEW / ISSUE INFORMATION
            |--------------------------------------------------------------------------
            */

            $table->string('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->string('issued_by')->nullable();
            $table->timestamp('issued_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | RETURN / CANCELLATION
            |--------------------------------------------------------------------------
            */

            $table->text('return_reason')->nullable();
            $table->text('cancellation_reason')->nullable();

            /*
            |--------------------------------------------------------------------------
            | GENERAL START NOTES
            |--------------------------------------------------------------------------
            */

            $table->text('notes')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | INDEXES
            |--------------------------------------------------------------------------
            */

            $table->index('casa_id');
            $table->index('proposal_id');
            $table->index('proposal_version_id');
            $table->index('status');
            $table->index('memo_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('start_records');
    }
};