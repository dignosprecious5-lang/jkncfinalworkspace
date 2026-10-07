<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Project;
use App\Models\RegularProject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TestDealsSeeder extends Seeder
{
    /**
     * Run database seeds with unique, distinct deal purchases across all pipeline stages.
     */
    public function run(): void
    {
        // 1. Remove duplicate test deals to keep the Kanban board clean
        Deal::where('deal_code', 'like', 'CONDEAL-2026-TEST-%')
            ->orWhere('deal_code', 'like', 'CONDEAL-2026-06%')
            ->orWhere('deal_code', 'like', 'CONDEAL-2026-07%')
            ->delete();

        $now = Carbon::now();

        // Distinct Test Deal Dataset across all 9 Kanban Stages
        $dealSpecs = [
            [
                'stage' => 'Inquiry',
                'company' => 'Starlight Tech Holdings Philippines Corp.',
                'contact_first' => 'Alexander',
                'contact_last' => 'Vance',
                'position' => 'Chief Technology Officer',
                'email' => 'a.vance@starlighttech.ph',
                'phone' => '+63 917 111 2233',
                'deal_name' => 'Fintech Licensing & SEC Registration Advisory',
                'service_area' => 'Financial Regulatory Advisory',
                'engagement_type' => 'Project',
                'value' => 180000.00,
                'status' => 'Pending Review',
                'details' => 'Client requested feasibility review for SEC fintech operator license.',
            ],
            [
                'stage' => 'Qualification',
                'company' => 'Vanguard Maritime & Shipping Corp.',
                'contact_first' => 'Elena',
                'contact_last' => 'Rostova',
                'position' => 'VP of Shipping Operations',
                'email' => 'e.rostova@vanguardmaritime.com',
                'phone' => '+63 918 222 3344',
                'deal_name' => 'Maritime Import Regulatory Permits & Customs Clearance',
                'service_area' => 'Customs & International Trade Advisory',
                'engagement_type' => 'Regular Retainer',
                'value' => 250000.00,
                'status' => 'Qualified Lead',
                'details' => 'Qualified for vessel import permits and MARINA compliance.',
            ],
            [
                'stage' => 'Consultation',
                'company' => 'Solaris Energy Solutions Inc.',
                'contact_first' => 'Marcus',
                'contact_last' => 'Thorne',
                'position' => 'Director of Business Development',
                'email' => 'm.thorne@solarisenergy.ph',
                'phone' => '+63 919 333 4455',
                'deal_name' => 'DOE Renewable Energy Project Compliance & ECC Clearance',
                'service_area' => 'Environmental & Energy Compliance',
                'engagement_type' => 'Project',
                'value' => 340000.00,
                'status' => 'In Consultation',
                'details' => 'Consultation session on Department of Energy solar farm permits.',
            ],
            [
                'stage' => 'Proposal',
                'company' => 'OmniCare Health & Pharmaceuticals Phils.',
                'contact_first' => 'Sophia',
                'contact_last' => 'Reyes',
                'position' => 'Regulatory Affairs Manager',
                'email' => 's.reyes@omnicarehealth.com',
                'phone' => '+63 920 444 5566',
                'deal_name' => 'FDA License to Operate (LTO) & Product Registration Retainer',
                'service_area' => 'Healthcare & FDA Regulatory Advisory',
                'engagement_type' => 'Regular Retainer',
                'value' => 290000.00,
                'status' => 'Proposal Sent',
                'details' => 'Engagement Proposal Agreement (EPA-2026-075) submitted for FDA LTO.',
            ],
            [
                'stage' => 'Negotiation',
                'company' => 'Horizon Infrastructure & Real Estate Group',
                'contact_first' => 'Gabriel',
                'contact_last' => 'Mendoza',
                'position' => 'Managing Director',
                'email' => 'g.mendoza@horizongroup.ph',
                'phone' => '+63 921 555 6677',
                'deal_name' => 'Corporate Restructuring & Land Title Transfer Retainer',
                'service_area' => 'Corporate Legal Advisory',
                'engagement_type' => 'Project',
                'value' => 420000.00,
                'status' => 'Under Negotiation',
                'details' => 'Finalizing fee structure and payment terms for corporate restructuring.',
            ],
            [
                'stage' => 'Payment',
                'company' => 'Titan Mining & Heavy Industries Inc.',
                'contact_first' => 'Victor',
                'contact_last' => 'Sterling',
                'position' => 'Chief Legal Officer',
                'email' => 'v.sterling@titanheavy.com',
                'phone' => '+63 922 666 7788',
                'deal_name' => 'DENR Mining Permit Compliance & Local Tax Settlement',
                'service_area' => 'Government & Tax Advisory',
                'engagement_type' => 'Project',
                'value' => 510000.00,
                'status' => 'Awaiting Payment',
                'details' => 'Contract approved. Invoice issued, awaiting 50% initial downpayment.',
            ],
            [
                'stage' => 'Activation',
                'company' => 'Beacon Logistics & Distribution Corp.',
                'contact_first' => 'Isabella',
                'contact_last' => 'Cruz',
                'position' => 'General Manager',
                'email' => 'i.cruz@beaconlogistics.ph',
                'phone' => '+63 923 777 8899',
                'deal_name' => 'BOC Customs Accreditation & Freight Compliance Retainer',
                'service_area' => 'Logistics & Trade Advisory',
                'engagement_type' => 'Regular Retainer',
                'value' => 380000.00,
                'status' => 'Activating',
                'details' => 'Downpayment confirmed. Setting up operational team and START form.',
            ],
            [
                'stage' => 'Closed Won',
                'company' => 'Apex Global Logistics Solutions Inc.',
                'contact_first' => 'Maria',
                'contact_last' => 'Garcia',
                'position' => 'Chief Operations Officer',
                'email' => 'm.garcia@apexglobal.com',
                'phone' => '+63 917 555 8921',
                'deal_name' => 'Share Transfer & Corporate Restructuring Activation',
                'service_area' => 'Corporate & Regulatory Advisory',
                'engagement_type' => 'Project',
                'value' => 450000.00,
                'status' => 'Closed Won',
                'details' => 'Deal fully approved and active in operations.',
            ],
            [
                'stage' => 'Closed Lost',
                'company' => 'Zenith Retail Ventures Inc.',
                'contact_first' => 'David',
                'contact_last' => 'Kim',
                'position' => 'Finance Director',
                'email' => 'd.kim@zenithretail.ph',
                'phone' => '+63 925 999 0011',
                'deal_name' => 'BIR Tax Audit Defense & Assessment Clearance',
                'service_area' => 'Tax Advisory',
                'engagement_type' => 'Project',
                'value' => 210000.00,
                'status' => 'Closed Lost',
                'details' => 'Client decided to handle tax audit internally.',
            ],
        ];

        foreach ($dealSpecs as $index => $spec) {
            $company = Company::firstOrCreate(
                ['company_name' => $spec['company']],
                [
                    'address' => 'Metropolitan Business District, Taguig City',
                    'email' => strtolower(str_replace(' ', '', $spec['company'])) . '@company.com',
                    'phone' => $spec['phone'],
                    'description' => $spec['service_area'],
                ]
            );

            $contact = Contact::firstOrCreate(
                ['email' => $spec['email']],
                [
                    'first_name' => $spec['contact_first'],
                    'last_name' => $spec['contact_last'],
                    'company_name' => $company->company_name,
                    'position' => $spec['position'],
                    'phone' => $spec['phone'],
                ]
            );

            $dealCode = Deal::generateNextDealCode();

            $deal = Deal::create([
                'deal_code' => $dealCode,
                'deal_name' => $spec['deal_name'],
                'contact_id' => $contact->id,
                'company_name' => $company->company_name,
                'company_address' => $company->address,
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'email' => $contact->email,
                'mobile' => $contact->phone,
                'position' => $contact->position,
                'stage' => $spec['stage'],
                'deal_status' => $spec['status'],
                'engagement_type' => $spec['engagement_type'],
                'total_estimated_engagement_value' => $spec['value'],
                'inquiry_details' => $spec['details'],
                'service_area' => $spec['service_area'],
                'planned_start_date' => $now->copy()->addDays(2 + $index)->format('Y-m-d'),
                'estimated_completion_date' => $now->copy()->addMonths(3 + $index)->format('Y-m-d'),
                'created_at' => $now->copy()->subDays(15 - $index),
            ]);

            // If Closed Won / Activated, ensure linked project engagement exists
            if (in_array($spec['stage'], ['Activated', 'Closed Won'])) {
                $projectCode = Project::generateNextProjectCode(null, $spec['engagement_type']);

                Project::firstOrCreate(
                    ['deal_id' => $deal->id],
                    [
                        'project_code' => $projectCode,
                        'company_id' => $company->id,
                        'contact_id' => $contact->id,
                        'name' => $spec['deal_name'],
                        'business_name' => $company->company_name,
                        'client_name' => $contact->first_name . ' ' . $contact->last_name,
                        'service_area' => $spec['service_area'],
                        'engagement_type' => $spec['engagement_type'],
                        'status' => 'In Planning',
                        'current_phase' => 'RSAT',
                        'assigned_project_manager' => 'John Kelly Alabado',
                        'assigned_consultant' => 'John Kelly Alabado',
                        'planned_start_date' => $now->copy()->subDays(1)->format('Y-m-d'),
                        'target_completion_date' => $now->copy()->addMonths(3)->format('Y-m-d'),
                    ]
                );

                RegularProject::firstOrCreate(
                    ['ref' => $projectCode],
                    [
                        'title' => $spec['deal_name'],
                        'data' => [
                            'deal_id' => $deal->id,
                            'company_name' => $company->company_name,
                            'contact_name' => $contact->first_name . ' ' . $contact->last_name,
                            'status' => 'In Planning',
                            'current_phase' => 'RSAT',
                        ],
                    ]
                );
            }
        }
    }
}
