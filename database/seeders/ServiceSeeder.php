<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;
use App\Models\ServiceVersion;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Safe foreign key toggle na gumagana sa SQLite at MySQL
        Schema::disableForeignKeyConstraints();
        Service::truncate();
        ServiceVersion::truncate();
        Schema::enableForeignKeyConstraints();

        $rawServices = [
            ['name' => 'On-site Profit and Loss Review', 'category' => 'Accountancy Revenue', 'engagement_behavior' => 'hybrid', 'frequency' => 'one_time', 'standard_price' => 3000.00, 'expected_hours' => 5],
            ['name' => 'Transfer of Shares of Stock Assistance', 'category' => 'Share Transfer & Stockholder Compliance', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 25000.00, 'expected_hours' => 20],
            ['name' => 'Middle Management Advisory Consultation', 'category' => 'Consulting Revenue', 'engagement_behavior' => 'hybrid', 'frequency' => 'monthly', 'standard_price' => 11000.00, 'expected_hours' => 10],
            ['name' => 'Managed Administrative Support Services', 'category' => 'Managed Administrative Support Services', 'engagement_behavior' => 'regular', 'frequency' => 'monthly', 'standard_price' => 35000.00, 'expected_hours' => 40],
            ['name' => 'Business Permit', 'category' => 'Compliance Revenue', 'engagement_behavior' => 'project', 'frequency' => 'annual', 'standard_price' => 3000.00, 'expected_hours' => 8],
            ['name' => 'BIR Registration- Update/ Change Information', 'category' => 'General Compliance', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 3000.00, 'expected_hours' => 6],
            ['name' => 'New LGU City/Municipality Business Registration Assistance — Non-Complex', 'category' => 'LGU and Local Permit Compliance', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 15000.00, 'expected_hours' => 15],
            ['name' => 'Domain Purchase and Setup Assistance', 'category' => 'Digital Business Setup', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 5000.00, 'expected_hours' => 4],
            ['name' => 'Professional Advisory Fee', 'category' => 'Professional Fees', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 565.00, 'expected_hours' => 2],
            ['name' => 'KPI & Performance Management Systems', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 25000.00, 'expected_hours' => 30],
            ['name' => 'BIR RDO Compliance Representative', 'category' => 'BIR and Tax Compliance', 'engagement_behavior' => 'regular', 'frequency' => 'monthly', 'standard_price' => 5000.00, 'expected_hours' => 8],
            ['name' => 'LGU Compliance Representative', 'category' => 'LGU and Local Permit Compliance', 'engagement_behavior' => 'regular', 'frequency' => 'monthly', 'standard_price' => 5000.00, 'expected_hours' => 8],
            ['name' => 'SEC Compliance Representative', 'category' => 'SEC and Corporate Compliance', 'engagement_behavior' => 'regular', 'frequency' => 'monthly', 'standard_price' => 5000.00, 'expected_hours' => 8],
            ['name' => 'Corporate Secretary Services (M)', 'category' => 'Corporate Officers Services', 'engagement_behavior' => 'regular', 'frequency' => 'monthly', 'standard_price' => 5000.00, 'expected_hours' => 10],
            ['name' => 'SAWT Preparation and eSubmission Validation', 'category' => 'BIR and Tax Compliance', 'engagement_behavior' => 'regular', 'frequency' => 'quarterly', 'standard_price' => 2500.00, 'expected_hours' => 4],
            ['name' => 'Bank Opening', 'category' => 'Financial Services', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 5000.00, 'expected_hours' => 6],
            ['name' => 'Bir Open Case Resolution', 'category' => 'BIR and Tax Compliance', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 5000.00, 'expected_hours' => 12],
            ['name' => 'Corporation Formation & Registration Assistance (M)', 'category' => 'Corporate Formation & Registration', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 15000.00, 'expected_hours' => 20],
            ['name' => 'Corporation Formation & Registration Assistance (S)', 'category' => 'Corporate Formation & Registration', 'engagement_behavior' => 'regular', 'frequency' => 'one_time', 'standard_price' => 20000.00, 'expected_hours' => 25],
            ['name' => 'Corporation Formation & Registration Assistance (MM)', 'category' => 'Corporate Formation & Registration', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 30000.00, 'expected_hours' => 35],
            ['name' => 'Corporation Formation & Registration Assistance (L)', 'category' => 'Corporate Formation & Registration', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 100000.00, 'expected_hours' => 80],
            ['name' => 'BIR Registration Assistance (M)', 'category' => 'BIR and Tax Compliance', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 15000.00, 'expected_hours' => 15],
            ['name' => 'BIR Registration Assistance (S)', 'category' => 'BIR and Tax Compliance', 'engagement_behavior' => 'regular', 'frequency' => 'one_time', 'standard_price' => 20000.00, 'expected_hours' => 20],
            ['name' => 'BIR Registration Assistance (MM)', 'category' => 'BIR and Tax Compliance', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 30000.00, 'expected_hours' => 30],
            ['name' => 'BIR Registration Assistance (L)', 'category' => 'BIR and Tax Compliance', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 100000.00, 'expected_hours' => 70],
            ['name' => 'New / Renewal LGU City/Municipality Business Registration Assistance — Complex', 'category' => 'LGU and Local Permit Compliance', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 25000.00, 'expected_hours' => 25],
            ['name' => 'Travel Credits — Cebu City, Mandaue City & Lapu-Lapu City', 'category' => 'Service Add-Ons', 'engagement_behavior' => 'hybrid', 'frequency' => 'one_time', 'standard_price' => 500.00, 'expected_hours' => 2],
            ['name' => 'Travel Credits — Metro Cebu', 'category' => 'Service Add-Ons', 'engagement_behavior' => 'hybrid', 'frequency' => 'one_time', 'standard_price' => 1000.00, 'expected_hours' => 3],
            ['name' => 'AMLC Registration and Compliance Officer Setup Assistance', 'category' => 'Corporate Compliance', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 5000.00, 'expected_hours' => 10],
            ['name' => 'Bookkeeping Services', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'monthly', 'standard_price' => 5000.00, 'expected_hours' => 15],
            ['name' => 'Transfer of BIR Registration from RDO 80 to RDO 81', 'category' => 'BIR Registration & Updates', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 15000.00, 'expected_hours' => 18],
            ['name' => 'Risk & Internal Control Setup', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 8],
            ['name' => 'Organizational Structuring', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 8],
            ['name' => 'Board Resolutions & Minutes', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 5],
            ['name' => 'Policy Development (HR, Finance, Ops)', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 12],
            ['name' => 'Corporate Officers Services', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'annual', 'standard_price' => 2500.00, 'expected_hours' => 10],
            ['name' => 'Corporate Secretary Services', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'annual', 'standard_price' => 2500.00, 'expected_hours' => 10],
            ['name' => 'Accounting Services', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'monthly', 'standard_price' => 2500.00, 'expected_hours' => 12],
            ['name' => 'Audit Support / Coordination', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'annual', 'standard_price' => 2500.00, 'expected_hours' => 15],
            ['name' => 'AFS Preparation', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'annual', 'standard_price' => 2500.00, 'expected_hours' => 15],
            ['name' => 'Tax Filing & Compliance (BIR)', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'quarterly', 'standard_price' => 2500.00, 'expected_hours' => 8],
            ['name' => 'Foreign Business Entry Support', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 20],
            ['name' => 'Loan Application Assistance', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 10],
            ['name' => 'Regulatory Compliance', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 10],
            ['name' => 'Business Registration (SEC / DTI / BIR)', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 15],
            ['name' => 'High-Risk / Complex Case Advisory', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 25],
            ['name' => 'HR Documentation & Contracts', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 8],
            ['name' => 'Executive / Virtual Assistant Support', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'monthly', 'standard_price' => 2500.00, 'expected_hours' => 20],
            ['name' => 'Accounting & Compliance Training', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 8],
            ['name' => 'Corporate Governance Workshops', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 8],
            ['name' => 'Business & Strategy Training', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 8],
            ['name' => 'Client Capability Development Programs', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 12],
            ['name' => 'JKNC Academy Courses', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 10],
            ['name' => 'Recruitment & Hiring Support', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 15],
            ['name' => 'Process Improvement / SOP Development', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 20],
            ['name' => 'Stakeholder Negotiation Support', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 10],
            ['name' => 'Business Restructuring Strategy', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 25],
            ['name' => 'Crisis Assessment & Stabilization', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 20],
            ['name' => 'Corporate Deadlock Resolution', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 18],
            ['name' => 'Financial Planning & Analysis', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 15],
            ['name' => 'Digital Transformation', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 30],
            ['name' => 'HR Structuring & Organization Design', 'category' => 'Advisory Revenue', 'engagement_behavior' => 'project', 'frequency' => 'one_time', 'standard_price' => 2500.00, 'expected_hours' => 15],
        ];

        foreach ($rawServices as $index => $item) {
            // Mapping: Isalin ang 'hybrid' sa 'both' para sumunod sa DB constraint
            $behavior = strtolower($item['engagement_behavior']);
            if ($behavior === 'hybrid') {
                $behavior = 'both';
            }

            $initialStatus = 'incomplete';

            $service = Service::create([
                'service_code'        => 'SVC-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'name'                => $item['name'],
                'short_name'          => Str::limit($item['name'], 25, ''),
                'service_area'        => 'Corporate & Regulatory Advisory',
                'category'            => $item['category'],
                'engagement_behavior' => $behavior,
                'status'              => $initialStatus,
            ]);

            $versionData = [
                'service_id'     => $service->id,
                'version_number' => 'V1.0',
                'status'         => $initialStatus,
                'is_active'      => true,
            ];

            if (Schema::hasColumn('service_versions', 'standard_price')) {
                $versionData['standard_price'] = $item['standard_price'];
            }
            if (Schema::hasColumn('service_versions', 'expected_hours')) {
                $versionData['expected_hours'] = $item['expected_hours'];
            }
            if (Schema::hasColumn('service_versions', 'activity_frequency')) {
                $versionData['activity_frequency'] = $item['frequency'];
            }

            $version = ServiceVersion::create($versionData);

            $service->update(['active_version_id' => $version->id]);
        }
    }
}