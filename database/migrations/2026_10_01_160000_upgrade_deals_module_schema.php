<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. ACCOUNTS TABLE
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table) {
                $table->id();
                $table->string('account_code', 50)->unique();
                $table->string('account_type', 50); // Business or Individual
                $table->string('account_name', 191);
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('individual_contact_id')->nullable();
                $table->string('status', 50)->default('Active');
                $table->timestamps();

                $table->index('account_type');
                $table->index('status');
                $table->index('company_id');
                $table->index('individual_contact_id');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 2. DEAL CONTACTS TABLE
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('deal_contacts')) {
            Schema::create('deal_contacts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('deal_id');
                $table->string('contact_type', 50)->default('Primary');
                $table->text('salutation')->nullable();
                $table->text('first_name')->nullable();
                $table->text('middle_name')->nullable();
                $table->text('last_name')->nullable();
                $table->text('name_extension')->nullable();
                $table->string('email', 191)->nullable();
                $table->text('mobile_number')->nullable();
                $table->text('address')->nullable();
                $table->text('position')->nullable();
                $table->boolean('is_primary')->default(true);
                $table->timestamps();

                $table->index('deal_id');
                $table->index('email');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 3. DEAL HISTORIES TABLE
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('deal_histories')) {
            Schema::create('deal_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('deal_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->text('user_name')->nullable();
                $table->string('activity_type', 50)->index();
                $table->text('title')->nullable();
                $table->text('description');
                $table->text('old_stage')->nullable();
                $table->text('new_stage')->nullable();
                $table->text('old_value')->nullable();
                $table->text('new_value')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['deal_id', 'created_at']);
                $table->index(['deal_id', 'activity_type']);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 4. CONVERT LARGE VARCHAR COLUMNS TO TEXT TO FREE ROW SIZE
        |--------------------------------------------------------------------------
        */
        $convertColumns = [
            'address',
            'company_address',
            'service_area',
            'services',
            'products',
            'payment_terms_other',
            'support_required',
            'service_department_unit',
            'internal_sales_marketing',
            'lead_associate_assigned',
            'internal_finance',
            'internal_president',
            'company_name',
            'deal_name',
            'qualification_result',
            'requirements_status',
            'timeline_notes',
            'client_search',
            'company',
            'primary_contact_name',
            'owner_name',
            'prepared_by',
            'reviewed_by',
            'approval_name',
            'referred_by',
            'sales_marketing',
            'lead_consultant',
            'lead_associate',
            'finance',
            'president',
            'record_custodian',
        ];

        foreach ($convertColumns as $col) {
            if (Schema::hasColumn('deals', $col)) {
                try {
                    DB::statement("ALTER TABLE `deals` MODIFY `{$col}` TEXT NULL");
                } catch (\Throwable $e) {
                    // Ignore if already text
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 5. ADD REMAINING STAGE WORKFLOW FIELDS TO DEALS
        |--------------------------------------------------------------------------
        */
        Schema::table('deals', function (Blueprint $table) {
            if (!Schema::hasColumn('deals', 'payment_status')) {
                $table->text('payment_status')->nullable();
            }
            if (!Schema::hasColumn('deals', 'payment_reference')) {
                $table->text('payment_reference')->nullable();
            }
            if (!Schema::hasColumn('deals', 'activation_date')) {
                $table->date('activation_date')->nullable();
            }
            if (!Schema::hasColumn('deals', 'assigned_team')) {
                $table->text('assigned_team')->nullable();
            }
            if (!Schema::hasColumn('deals', 'assigned_person')) {
                $table->text('assigned_person')->nullable();
            }
            if (!Schema::hasColumn('deals', 'service_start_date')) {
                $table->date('service_start_date')->nullable();
            }
            if (!Schema::hasColumn('deals', 'activation_notes')) {
                $table->text('activation_notes')->nullable();
            }
            if (!Schema::hasColumn('deals', 'closed_won_date')) {
                $table->date('closed_won_date')->nullable();
            }
            if (!Schema::hasColumn('deals', 'closed_lost_date')) {
                $table->date('closed_lost_date')->nullable();
            }
            if (!Schema::hasColumn('deals', 'lost_reason')) {
                $table->text('lost_reason')->nullable();
            }
            if (!Schema::hasColumn('deals', 'closing_notes')) {
                $table->text('closing_notes')->nullable();
            }
        });
    }

    public function down(): void
    {
    }
};
