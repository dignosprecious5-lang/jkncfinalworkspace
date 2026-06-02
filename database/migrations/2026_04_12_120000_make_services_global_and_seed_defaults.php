<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('services')) {
            return;
        }

        if (Schema::hasColumn('services', 'company_id')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
            });

            if (DB::getDriverName() !== 'sqlite') {
                DB::statement('ALTER TABLE `services` MODIFY `company_id` BIGINT UNSIGNED NULL');
            }

            Schema::table('services', function (Blueprint $table) {
                $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            });
        }

        $catalog = [
            'Corporate & Regulatory Advisory' => [
                'Business Registration (SEC / DTI / BIR)',
                'Business Permit Processing / Renewal',
                'Regulatory Compliance',
                'Loan Application Assistance',
                'Foreign Business Entry Support',
            ],
            'Accounting & Compliance Advisory' => [
                'Bookkeeping Services',
                'Tax Filing & Compliance (BIR)',
                'AFS Preparation',
                'Audit Support / Coordination',
                'Accounting Services',
            ],
            'Governance & Policy Advisory' => [
                'Corporate Secretary Services',
                'Corporate Officers Services',
                'Policy Development (HR, Finance, Ops)',
                'Board Resolutions & Minutes',
                'Risk & Internal Control Setup',
            ],
            'Business Strategy & Process Advisory' => [
                'Business Consulting / Strategy',
                'Process Improvement / SOP Development',
                'Organizational Structuring',
                'Digital Transformation',
                'Financial Planning & Analysis',
            ],
            'Strategic Situations Advisory' => [
                'Corporate Deadlock Resolution',
                'Crisis Assessment & Stabilization',
                'Business Restructuring Strategy',
                'Stakeholder Negotiation Support',
                'High-Risk / Complex Case Advisory',
            ],
            'People & Talent Solutions' => [
                'Recruitment & Hiring Support',
                'HR Structuring & Organization Design',
                'KPI & Performance Management Systems',
                'HR Documentation & Contracts',
                'Executive / Virtual Assistant Support',
            ],
            'Learning & Capability Development' => [
                'Accounting & Compliance Training',
                'Corporate Governance Workshops',
                'Business & Strategy Training',
                'Client Capability Development Programs',
                'JKNC Academy Courses',
            ],
        ];

        $now = Carbon::now();

        foreach ($catalog as $area => $services) {
            foreach ($services as $serviceName) {
                $exists = DB::table('services')
                    ->where('service_name', $serviceName)
                    ->when(Schema::hasColumn('services', 'company_id'), fn ($query) => $query->whereNull('company_id'))
                    ->exists();

                if ($exists) {
                    continue;
                }

                $serviceId = $this->nextServiceId();

                DB::table('services')->insert([
                    'company_id' => null,
                    'service_id' => $serviceId,
                    'service_name' => $serviceName,
                    'service_description' => $serviceName,
                    'service_activity_output' => $serviceName,
                    'service_area' => json_encode([$area], JSON_UNESCAPED_UNICODE),
                    'service_area_other' => null,
                    'category' => 'Advisory Revenue',
                    'frequency' => 'One-time',
                    'schedule_rule' => null,
                    'deadline' => null,
                    'reminder_lead_time' => null,
                    'requirements' => null,
                    'requirement_category' => null,
                    'engagement_structure' => json_encode(['Project Engagement'], JSON_UNESCAPED_UNICODE),
                    'is_recurring' => false,
                    'unit' => 'Project',
                    'rate_per_unit' => null,
                    'min_units' => null,
                    'max_cap' => null,
                    'price_fee' => 2500,
                    'cost_of_service' => null,
                    'tax_type' => 'Tax Exclusive',
                    'assigned_unit' => 'Operations',
                    'status' => 'Active',
                    'created_by' => null,
                    'reviewed_at' => null,
                    'reviewed_by' => null,
                    'approved_at' => null,
                    'approved_by' => null,
                    'custom_field_values' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('services')) {
            return;
        }

        $serviceNames = [
            'Business Registration (SEC / DTI / BIR)',
            'Business Permit Processing / Renewal',
            'Regulatory Compliance',
            'Loan Application Assistance',
            'Foreign Business Entry Support',
            'Bookkeeping Services',
            'Tax Filing & Compliance (BIR)',
            'AFS Preparation',
            'Audit Support / Coordination',
            'Accounting Services',
            'Corporate Secretary Services',
            'Corporate Officers Services',
            'Policy Development (HR, Finance, Ops)',
            'Board Resolutions & Minutes',
            'Risk & Internal Control Setup',
            'Business Consulting / Strategy',
            'Process Improvement / SOP Development',
            'Organizational Structuring',
            'Digital Transformation',
            'Financial Planning & Analysis',
            'Corporate Deadlock Resolution',
            'Crisis Assessment & Stabilization',
            'Business Restructuring Strategy',
            'Stakeholder Negotiation Support',
            'High-Risk / Complex Case Advisory',
            'Recruitment & Hiring Support',
            'HR Structuring & Organization Design',
            'KPI & Performance Management Systems',
            'HR Documentation & Contracts',
            'Executive / Virtual Assistant Support',
            'Accounting & Compliance Training',
            'Corporate Governance Workshops',
            'Business & Strategy Training',
            'Client Capability Development Programs',
            'JKNC Academy Courses',
        ];

        DB::table('services')
            ->whereNull('company_id')
            ->whereIn('service_name', $serviceNames)
            ->delete();
    }

    private function nextServiceId(): string
    {
        do {
            $candidate = (string) random_int(10000, 99999);
        } while (DB::table('services')->where('service_id', $candidate)->exists());

        return $candidate;
    }
};
