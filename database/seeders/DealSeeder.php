<?php

namespace Database\Seeders;

use App\Models\Deal;
use Illuminate\Database\Seeder;

class DealSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $deals = [
            ['deal_code' => 'CONDEAL-2026-101', 'pipeline_stage' => 'Inquiry', 'deal_title' => 'Digital Finance Readiness Review', 'company' => 'Northstar Foods Inc.', 'amount' => 85000, 'expected_close' => '2026-09-15', 'owner_name' => 'Maya Santos'],
            ['deal_code' => 'CONDEAL-2026-102', 'pipeline_stage' => 'Qualification', 'deal_title' => 'Regional Expansion Compliance Plan', 'company' => 'Harborline Logistics', 'amount' => 145000, 'expected_close' => '2026-09-22', 'owner_name' => 'Andre Villanueva'],
            ['deal_code' => 'CONDEAL-2026-103', 'pipeline_stage' => 'Consultation', 'deal_title' => 'People Operations Advisory', 'company' => 'Brightpath Learning Center', 'amount' => 112500, 'expected_close' => '2026-10-03', 'owner_name' => 'Leah Mercado'],
            ['deal_code' => 'CONDEAL-2026-104', 'pipeline_stage' => 'Proposal', 'deal_title' => 'Corporate Governance Refresh', 'company' => 'Cedar Peak Holdings', 'amount' => 198000, 'expected_close' => '2026-09-30', 'owner_name' => 'Jonas Reyes'],
            ['deal_code' => 'CONDEAL-2026-105', 'pipeline_stage' => 'Negotiation', 'deal_title' => 'ERP Process Improvement Program', 'company' => 'Solterra Manufacturing', 'amount' => 325000, 'expected_close' => '2026-10-18', 'owner_name' => 'Nina Castillo'],
            ['deal_code' => 'CONDEAL-2026-106', 'pipeline_stage' => 'Payment', 'deal_title' => 'Annual Tax Advisory Retainer', 'company' => 'Blue Oak Retail Group', 'amount' => 176000, 'expected_close' => '2026-09-12', 'owner_name' => 'Paolo Garcia'],
            ['deal_code' => 'CONDEAL-2026-107', 'pipeline_stage' => 'Activation', 'deal_title' => 'Workforce Capability Workshop', 'company' => 'Evergreen Health Partners', 'amount' => 92000, 'expected_close' => '2026-09-08', 'owner_name' => 'Tessa Lim'],
            ['deal_code' => 'CONDEAL-2026-108', 'pipeline_stage' => 'Closed Won', 'deal_title' => 'Regulatory Filing Support', 'company' => 'Westbridge Energy Corp.', 'amount' => 240000, 'expected_close' => '2026-08-29', 'owner_name' => 'Rafael Cruz'],
            ['deal_code' => 'CONDEAL-2026-109', 'pipeline_stage' => 'Closed Lost', 'deal_title' => 'Market Entry Assessment', 'company' => 'Lighthouse Consumer Goods', 'amount' => 67500, 'expected_close' => '2026-08-25', 'owner_name' => 'Iris Navarro'],
        ];

        foreach ($deals as $deal) {
            Deal::updateOrCreate(['deal_code' => $deal['deal_code']], $deal);
        }
    }
}
