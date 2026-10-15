<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FullServiceSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        ServiceVersion::truncate();
        Service::truncate();
        Schema::enableForeignKeyConstraints();

        $services = [
            ['name' => 'Annual Tax Compliance Advisory', 'category' => 'Tax Services', 'service_area' => 'Tax Services', 'engagement_behavior' => 'regular', 'status' => 'active'],
            ['name' => 'BIR Annual Registration Renewal', 'category' => 'Registration', 'service_area' => 'Government Processing', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Bookkeeping & Accounting (Monthly)', 'category' => 'Bookkeeping', 'service_area' => 'Government Processing', 'engagement_behavior' => 'regular', 'status' => 'active'],
            ['name' => 'Business Permit Renewal (LGU)', 'category' => 'Registration', 'service_area' => 'Government Processing', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Corporate Income Tax Filing (1702-EX)', 'category' => 'Corporate Tax', 'service_area' => 'Tax Services', 'engagement_behavior' => 'regular', 'status' => 'active'],
            ['name' => 'Corporate Income Tax Filing (1702-RT)', 'category' => 'Corporate Tax', 'service_area' => 'Tax Services', 'engagement_behavior' => 'regular', 'status' => 'active'],
            ['name' => 'Corporate Income Tax Filing (1702-MX)', 'category' => 'Corporate Tax', 'service_area' => 'Tax Services', 'engagement_behavior' => 'regular', 'status' => 'active'],
            ['name' => 'Quarterly Corporate Income Tax (1702Q)', 'category' => 'Corporate Tax', 'service_area' => 'Tax Services', 'engagement_behavior' => 'regular', 'status' => 'active'],
            ['name' => 'Monthly Value Added Tax (VAT 2550M)', 'category' => 'Tax Services', 'service_area' => 'Tax Services', 'engagement_behavior' => 'regular', 'status' => 'active'],
            ['name' => 'Quarterly Value Added Tax (VAT 2550Q)', 'category' => 'Tax Services', 'service_area' => 'Tax Services', 'engagement_behavior' => 'regular', 'status' => 'active'],
            ['name' => 'Percentage Tax Returns (2551Q)', 'category' => 'Tax Services', 'service_area' => 'Tax Services', 'engagement_behavior' => 'regular', 'status' => 'active'],
            ['name' => 'Expanded Withholding Tax (0619-E / 1601-EQ)', 'category' => 'Tax Services', 'service_area' => 'Tax Services', 'engagement_behavior' => 'regular', 'status' => 'active'],
            ['name' => 'Withholding Tax on Compensation (1601-C)', 'category' => 'Tax Services', 'service_area' => 'Tax Services', 'engagement_behavior' => 'regular', 'status' => 'active'],
            ['name' => 'Final Withholding Tax (1601-FQ)', 'category' => 'Tax Services', 'service_area' => 'Tax Services', 'engagement_behavior' => 'regular', 'status' => 'active'],
            ['name' => 'Annual Information Returns (1604-C / 1604-E)', 'category' => 'Compliance', 'service_area' => 'Tax Services', 'engagement_behavior' => 'regular', 'status' => 'active'],
            ['name' => 'SEC General Information Sheet (GIS) Filing', 'category' => 'Compliance', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'SEC Annual Financial Statements (AFS) Filing', 'category' => 'Compliance', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'SEC Company Registration & Incorporation', 'category' => 'Registration', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'BIR Initial Business Registration (Form 1903)', 'category' => 'Registration', 'service_area' => 'Government Processing', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'BIR Books of Accounts Stamping', 'category' => 'Registration', 'service_area' => 'Government Processing', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'BIR Authority to Print (ATP) Receipts', 'category' => 'Registration', 'service_area' => 'Government Processing', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Social Security System (SSS) Registration', 'category' => 'Registration', 'service_area' => 'Government Processing', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'PhilHealth Employer Registration', 'category' => 'Registration', 'service_area' => 'Government Processing', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Pag-IBIG Employer Registration', 'category' => 'Registration', 'service_area' => 'Government Processing', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Payroll Processing & Management (Bi-Monthly)', 'category' => 'Bookkeeping', 'service_area' => 'Government Processing', 'engagement_behavior' => 'regular', 'status' => 'active'],
            ['name' => 'Transfer of Shares Advisory', 'category' => 'Transfer of Shares', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Capital Stock Increase (SEC Approval)', 'category' => 'Compliance', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Amended Articles of Incorporation Processing', 'category' => 'Compliance', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Amended By-Laws Processing', 'category' => 'Compliance', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Corporate Dissolution & Liquidation', 'category' => 'Advisory', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Tax Health Check & Audit Preparation', 'category' => 'Advisory', 'service_area' => 'Tax Services', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'BIR Notice of Disallowance / Assessment Assistance', 'category' => 'Advisory', 'service_area' => 'Tax Services', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Certificate of Tax Exemption Application', 'category' => 'Tax Services', 'service_area' => 'Tax Services', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'DOLE Rule 1020 Registration', 'category' => 'Compliance', 'service_area' => 'Government Processing', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'PEZA Enterprise Registration', 'category' => 'Registration', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'BOI Project Registration', 'category' => 'Registration', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Customs Client Profile Registration (CPR)', 'category' => 'Registration', 'service_area' => 'Government Processing', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Trademark & Intellectual Property Filing', 'category' => 'Compliance', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Financial Due Diligence Review', 'category' => 'Advisory', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Internal Controls & Process Review', 'category' => 'Advisory', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Transfer Pricing Documentation (TPD)', 'category' => 'Tax Services', 'service_area' => 'Tax Services', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'One Person Corporation (OPC) Registration', 'category' => 'Registration', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Partnership Formation & Registration', 'category' => 'Registration', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Sole Proprietorship DTI Registration', 'category' => 'Registration', 'service_area' => 'Government Processing', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'BIR Tax Clearance Certificate Processing', 'category' => 'Compliance', 'service_area' => 'Tax Services', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Certificate of Inward Remittance Processing', 'category' => 'Advisory', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Foreign Corporation Branch Office Registration', 'category' => 'Registration', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Representative Office SEC Registration', 'category' => 'Registration', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Regional Headquarters (RHQ) Registration', 'category' => 'Registration', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Alien Employment Permit (AEP) Processing', 'category' => 'Compliance', 'service_area' => 'Government Processing', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => '9(g) Working Visa Application Processing', 'category' => 'Compliance', 'service_area' => 'Government Processing', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Special Investor Resident Visa (SIRV) Assistance', 'category' => 'Advisory', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Inventory System Stamping & Approval (BIR)', 'category' => 'Compliance', 'service_area' => 'Tax Services', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Computerized Accounting System (CAS) Permit', 'category' => 'Compliance', 'service_area' => 'Tax Services', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'eFPS Enrollment Assistance', 'category' => 'Registration', 'service_area' => 'Government Processing', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Local Business Tax Protest & Appeal', 'category' => 'Advisory', 'service_area' => 'Tax Services', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Real Property Tax Assessment Review', 'category' => 'Advisory', 'service_area' => 'Tax Services', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Estate Tax Settlement & Filing Assistance', 'category' => 'Tax Services', 'service_area' => 'Tax Services', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Donor Tax Filing & Processing', 'category' => 'Tax Services', 'service_area' => 'Tax Services', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Capital Gains Tax (CGT) Processing', 'category' => 'Tax Services', 'service_area' => 'Tax Services', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Documentary Stamp Tax (DST) Filing', 'category' => 'Tax Services', 'service_area' => 'Tax Services', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Corporate Secretary Retainer Service', 'category' => 'Compliance', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'regular', 'status' => 'active'],
        ];

        $i = 1;
        foreach ($services as $data) {
            $service = Service::create([
                'service_code' => 'SVC-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'name' => $data['name'],
                'category' => $data['category'],
                'service_area' => $data['service_area'],
                'engagement_behavior' => $data['engagement_behavior'],
                'status' => $data['status'],
            ]);

            ServiceVersion::create([
                'service_id' => $service->id,
                'version_number' => 'V1.0',
                'standard_price' => rand(50, 250) * 100,
                'expected_hours' => rand(8, 40),
                'activity_frequency' => ($data['engagement_behavior'] === 'regular') ? 'Monthly' : 'One-time',
                'is_active' => true,
            ]);

            $i++;
        }
    }
}