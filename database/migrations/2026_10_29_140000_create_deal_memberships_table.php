<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deal_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('deal_id')->constrained('deals')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->enum('membership_role', ['consultant', 'associate', 'lead_consultant', 'lead_associate']);
            $table->enum('assignment_status', ['active', 'inactive'])->default('active');
            $table->foreignId('assigned_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('status_changed_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('status_changed_at')->nullable();
            $table->string('active_assignment_key', 128)
                ->nullable()
                ->storedAs("CASE WHEN assignment_status = 'active' THEN CONCAT(deal_id, ':', user_id, ':', membership_role) ELSE NULL END");
            $table->timestamps();

            $table->unique('active_assignment_key', 'deal_memberships_active_unique');
            $table->index(['deal_id', 'assignment_status', 'membership_role', 'user_id'], 'deal_memberships_deal_active_lookup');
            $table->index(['user_id', 'assignment_status', 'deal_id'], 'deal_memberships_user_active_lookup');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('deal_memberships') && \Illuminate\Support\Facades\DB::table('deal_memberships')->exists()) {
            throw new RuntimeException('deal_memberships contains retained assignment history and cannot be dropped safely.');
        }

        Schema::dropIfExists('deal_memberships');
    }
};
