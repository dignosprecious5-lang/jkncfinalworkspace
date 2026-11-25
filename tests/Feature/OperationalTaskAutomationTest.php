<?php

namespace Tests\Feature;

use App\Models\Engagement;
use App\Models\EngagementPeriod;
use App\Models\OperationalTask;
use App\Models\Service;
use App\Models\ServiceActivity;
use App\Models\ServiceVersion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OperationalTaskAutomationTest extends TestCase
{
    public function refreshDatabase(): void {}

    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        foreach ([
            'operational_tasks',
            'engagement_periods',
            'engagements',
            'service_activities',
            'service_versions',
            'services',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('service_id')->nullable();
            $table->string('service_code')->nullable();
            $table->string('name');
            $table->string('service_name')->nullable();
            $table->text('service_description')->nullable();
            $table->text('service_activity_output')->nullable();
            $table->json('service_area')->nullable();
            $table->string('category')->nullable();
            $table->string('engagement_behavior')->nullable();
            $table->json('engagement_structure')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('active_version_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('service_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->integer('version_number')->default(1);
            $table->string('status')->default('active');
            $table->boolean('is_active')->default(true);
            $table->string('currency')->default('PHP');
            $table->string('pricing_model')->default('Fixed Fee');
            $table->string('tax_treatment')->default('VAT Exclusive');
            $table->string('payment_structure')->default('Full Advance');
            $table->string('instantiation_mode')->default('automatic');
            $table->string('recurrence_frequency')->nullable();
            $table->date('base_reference_date')->nullable();
            $table->boolean('auto_carryover')->default(true);
            $table->integer('instantiation_lead_days')->default(15);
            $table->integer('internal_reminder_days')->default(3);
            $table->integer('escalation_threshold_days')->default(2);
            $table->timestamps();
        });

        Schema::create('service_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_version_id')->constrained('service_versions')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('service_activities')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('sequence')->default(1);
            $table->boolean('is_mandatory')->default(true);
            $table->decimal('expected_working_hours', 8, 2)->default(8.00);
            $table->integer('expected_days')->default(1);
            $table->boolean('is_billable')->default(true);
            $table->boolean('include_in_report')->default(true);
            $table->timestamps();
        });

        Schema::create('engagements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_version_id')->nullable();
            $table->string('engagement_type')->default('regular');
            $table->string('status')->default('active');
            $table->date('start_date')->nullable();
            $table->timestamps();
        });

        Schema::create('engagement_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('engagement_id')->constrained('engagements')->cascadeOnDelete();
            $table->foreignId('service_version_id')->constrained('service_versions')->cascadeOnDelete();
            $table->string('period_name');
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->string('status')->default('pending');
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('operational_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('engagement_id')->nullable()->constrained('engagements')->nullOnDelete();
            $table->foreignId('engagement_period_id')->nullable()->constrained('engagement_periods')->cascadeOnDelete();
            $table->foreignId('parent_task_id')->nullable()->constrained('operational_tasks')->nullOnDelete();
            $table->foreignId('service_activity_id')->nullable()->constrained('service_activities')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedInteger('expected_days')->default(1);
            $table->unsignedInteger('expected_working_hours')->default(8);
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_billable')->default(true);
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique(['engagement_period_id', 'service_activity_id'], 'unique_period_activity_task');
        });
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ([
            'operational_tasks',
            'engagement_periods',
            'engagements',
            'service_activities',
            'service_versions',
            'services',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    #[Test]
    public function test_it_runs_task_automation_successfully(): void
    {
        $service = Service::create([
            'service_id' => '99101',
            'service_code' => 'SRV-AUTO-01',
            'name' => 'Monthly Compliance Retainer',
            'service_name' => 'Monthly Compliance Retainer',
            'status' => 'active',
        ]);

        $serviceVersion = ServiceVersion::create([
            'service_id' => $service->id,
            'version_number' => 1,
            'status' => 'active',
            'is_active' => true,
            'recurrence_frequency' => 'monthly',
            'base_reference_date' => now()->startOfMonth()->toDateString(),
        ]);

        $parentActivity = ServiceActivity::create([
            'service_version_id' => $serviceVersion->id,
            'name' => 'Prepare Monthly Tax Filing',
            'description' => 'Automated recurring monthly filing task',
            'sequence' => 1,
            'expected_days' => 2,
            'expected_working_hours' => 8,
            'is_mandatory' => true,
            'is_billable' => true,
        ]);

        $engagement = Engagement::create([
            'service_version_id' => $serviceVersion->id,
            'status' => 'active',
        ]);

        $this->artisan('services:instantiate-periods')
            ->assertExitCode(0);

        $period = EngagementPeriod::where('engagement_id', $engagement->id)
            ->where('service_version_id', $serviceVersion->id)
            ->first();

        $this->assertNotNull($period, 'Expected an engagement period to be instantiated for the active engagement.');

        $this->assertDatabaseHas('operational_tasks', [
            'engagement_id' => $engagement->id,
            'engagement_period_id' => $period->id,
            'service_activity_id' => $parentActivity->id,
            'title' => 'Prepare Monthly Tax Filing',
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function test_it_does_not_duplicate_tasks_when_period_already_exists(): void
    {
        $service = Service::create([
            'service_id' => '99102',
            'service_code' => 'SRV-AUTO-02',
            'name' => 'Quarterly Audit Review',
            'service_name' => 'Quarterly Audit Review',
            'status' => 'active',
        ]);

        $serviceVersion = ServiceVersion::create([
            'service_id' => $service->id,
            'version_number' => 1,
            'status' => 'active',
            'is_active' => true,
            'recurrence_frequency' => 'monthly',
            'base_reference_date' => now()->startOfMonth()->toDateString(),
        ]);

        $activity = ServiceActivity::create([
            'service_version_id' => $serviceVersion->id,
            'name' => 'Compile Audit Working Papers',
            'sequence' => 1,
            'expected_days' => 3,
            'expected_working_hours' => 12,
        ]);

        $engagement = Engagement::create([
            'service_version_id' => $serviceVersion->id,
            'status' => 'active',
        ]);

        $this->artisan('services:instantiate-periods')->assertExitCode(0);

        $firstPeriod = EngagementPeriod::where('engagement_id', $engagement->id)->firstOrFail();
        $this->assertSame(
            1,
            OperationalTask::where('engagement_period_id', $firstPeriod->id)
                ->where('service_activity_id', $activity->id)
                ->count()
        );

        // Re-running instantiation on the same period must not duplicate tasks.
        app(\App\Services\ServiceAutomationManager::class)->processEngagement($engagement->fresh());

        $this->assertSame(
            1,
            OperationalTask::where('engagement_period_id', $firstPeriod->id)
                ->where('service_activity_id', $activity->id)
                ->count()
        );
    }
}
