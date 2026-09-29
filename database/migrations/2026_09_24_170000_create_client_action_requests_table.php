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
        if (!Schema::hasTable('client_action_requests')) {
            Schema::create('client_action_requests', function (Blueprint $table) {
                $table->id();
                $table->string('action_code')->unique(); // e.g. CA-2026-001
                $table->foreignId('deal_id')->nullable()->constrained('deals')->nullOnDelete();
                $table->nullableMorphs('actionable'); // actionable_type, actionable_id
                
                // Document & Version References
                $table->string('document_type')->default('Proposal'); // Proposal, CASA, START Service Memo, etc.
                $table->string('document_reference')->nullable(); // Reference code (e.g. PROP-2026-001)
                $table->string('document_version')->default('V1'); // V1, V2, V3, etc.
                $table->string('document_title')->nullable(); // Title/Name of the document
                $table->string('document_url')->nullable();

                // Contact & Client References
                $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
                $table->string('client_name');
                $table->string('client_email')->nullable();
                $table->string('client_phone')->nullable();

                // Requested Action
                $table->string('action_type'); // Review, Select, Accept, Approve, Sign, Acknowledge, Upload, Respond
                $table->string('title');
                $table->text('instructions')->nullable();

                // Status & Lifecycle
                $table->string('status')->default('Pending'); // Pending, Awaiting Client, In Progress, Approved, Accepted, Declined, Acknowledged, Uploaded, Signed, Completed, Expired, Cancelled
                $table->string('response_channel')->nullable(); // Portal, Secure Link, Email, Manual / Wet Signature, External Signing

                // Security & Token
                $table->string('secure_token', 64)->unique();
                $table->boolean('requires_otp')->default(false);
                $table->string('otp_code', 10)->nullable();
                $table->dateTime('otp_expires_at')->nullable();
                $table->dateTime('expires_at')->nullable();

                // Event Timestamps
                $table->dateTime('sent_at')->nullable();
                $table->dateTime('opened_at')->nullable();
                $table->dateTime('responded_at')->nullable();
                $table->dateTime('completed_at')->nullable();

                // Response Data & Decision
                $table->string('response_decision')->nullable();
                $table->text('response_notes')->nullable();
                $table->json('response_data')->nullable();

                // Evidence
                $table->string('evidence_type')->nullable(); // Portal, Secure Link, Email, Signed Document, External Proof
                $table->string('evidence_file_path')->nullable();
                $table->string('evidence_file_name')->nullable();
                $table->text('evidence_notes')->nullable();
                $table->json('evidence_metadata')->nullable();

                // Manual / Wet & External Signatures
                $table->string('signatory_name')->nullable();
                $table->date('document_signed_date')->nullable();
                $table->string('verification_state')->default('Unverified'); // Unverified, Verified, Rejected

                // Audit & Recording Users
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('recorded_by_name')->nullable();
                $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();

                $table->json('audit_trail')->nullable();

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_action_requests');
    }
};
