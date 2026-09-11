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
        // 1. Kung wala pa ang table, buuhin muna ang base table
        if (!Schema::hasTable('service_versions')) {
            Schema::create('service_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('version_number')->default('V1.0');
                $table->decimal('standard_price', 12, 2)->default(0);
                $table->integer('expected_hours')->default(0);
                $table->boolean('is_active')->default(true);
                $table->string('status')->default('draft');
                $table->timestamps();
            });
        }

        // 2. Idagdag ang mga karagdagang fields (Overview, Proposal & Commercials)
        Schema::table('service_versions', function (Blueprint $table) {
            
            // --- OVERVIEW FIELDS ---
            if (!Schema::hasColumn('service_versions', 'short_name')) {
                $table->string('short_name')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'expected_turnaround')) {
                $table->string('expected_turnaround')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'effective_date')) {
                $table->date('effective_date')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'internal_description')) {
                $table->text('internal_description')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'client_description')) {
                $table->text('client_description')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'about_service')) {
                $table->text('about_service')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'purpose')) {
                $table->text('purpose')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'when_to_use')) {
                $table->text('when_to_use')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'what_it_is_not')) {
                $table->string('what_it_is_not')->nullable();
            }

            // --- PROPOSAL FIELDS ---
            if (!Schema::hasColumn('service_versions', 'scope_of_work')) {
                $table->text('scope_of_work')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'exclusions')) {
                $table->text('exclusions')->nullable();
            }

            // --- COMMERCIALS FIELDS ---
            if (!Schema::hasColumn('service_versions', 'ope_included')) {
                $table->boolean('ope_included')->default(false);
            }
            if (!Schema::hasColumn('service_versions', 'activity_frequency')) {
                $table->string('activity_frequency')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'billing_frequency')) {
                $table->string('billing_frequency')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'reporting_frequency')) {
                $table->string('reporting_frequency')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('service_versions')) {
            Schema::table('service_versions', function (Blueprint $table) {
                $columnsToDrop = [];

                $columns = [
                    'short_name',
                    'expected_turnaround',
                    'effective_date',
                    'internal_description',
                    'client_description',
                    'about_service',
                    'purpose',
                    'when_to_use',
                    'what_it_is_not',
                    'scope_of_work',
                    'exclusions',
                    'ope_included',
                    'activity_frequency',
                    'billing_frequency',
                    'reporting_frequency',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('service_versions', $column)) {
                        $columnsToDrop[] = $column;
                    }
                }

                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }
    }
};