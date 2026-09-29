<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Deal;
use App\Models\DealHistory;
use App\Models\User;
use App\Services\DealHistoryService;
use Carbon\Carbon;

class DealHistorySeeder extends Seeder
{
    public function run(): void
    {
        $deals = Deal::all();
        $users = User::all();
        $adminUser = $users->first();
        $historyService = app(DealHistoryService::class);

        foreach ($deals as $deal) {
            // Check if histories already exist for this deal
            if ($deal->histories()->count() > 0) {
                continue;
            }

            $createdAt = $deal->created_at ? Carbon::parse($deal->created_at) : Carbon::now()->subDays(10);
            $owner = $deal->owner_name ?: ($adminUser ? $adminUser->name : 'John Kelly Abalde');

            // 1. Initial Deal Creation
            $h1 = $historyService->logActivity(
                $deal,
                DealHistory::TYPE_DEAL_CREATED,
                "Deal \"{$deal->deal_title}\" was created by {$owner} with code {$deal->deal_code}.",
                [
                    'user_id' => $adminUser?->id,
                    'user_name' => $owner,
                    'to_stage' => 'Inquiry',
                    'new_values' => [
                        'deal_title' => $deal->deal_title,
                        'deal_code' => $deal->deal_code,
                        'engagement_type' => $deal->engagement_type,
                        'amount' => $deal->amount,
                        'pipeline_stage' => 'Inquiry',
                    ],
                ]
            );
            $h1->update(['created_at' => $createdAt->copy(), 'updated_at' => $createdAt->copy()]);

            // 2. Initial Note Added
            $h2 = $historyService->logActivity(
                $deal,
                DealHistory::TYPE_NOTE_ADDED,
                "Added initial client requirement notes: \"Initial intake discussion completed with {$deal->primary_contact_name}\"",
                [
                    'user_id' => $adminUser?->id,
                    'user_name' => $owner,
                    'notes' => "Client requested comprehensive advisory and statutory filings for {$deal->company}.",
                    'new_values' => ['category' => 'Intake', 'contact' => $deal->primary_contact_name],
                ]
            );
            $h2->update(['created_at' => $createdAt->copy()->addHours(2), 'updated_at' => $createdAt->copy()->addHours(2)]);

            // 3. Stage transition to Qualification
            $h3 = $historyService->logActivity(
                $deal,
                DealHistory::TYPE_STAGE_CHANGED,
                "Stage transitioned from Inquiry to Qualification.",
                [
                    'user_id' => $adminUser?->id,
                    'user_name' => $owner,
                    'from_stage' => 'Inquiry',
                    'to_stage' => 'Qualification',
                    'field_name' => 'pipeline_stage',
                    'from_value' => 'Inquiry',
                    'to_value' => 'Qualification',
                    'notes' => 'Customer KYC passed initial qualification checks.',
                ]
            );
            $h3->update(['created_at' => $createdAt->copy()->addDays(1)->addHours(3), 'updated_at' => $createdAt->copy()->addDays(1)->addHours(3)]);

            // 4. Team Assignment
            $h4 = $historyService->logActivity(
                $deal,
                DealHistory::TYPE_ASSIGNMENT_CHANGED,
                "Assigned Lead Consultant: " . ($deal->lead_consultant ?: 'John Kelly Abalde') . " and Lead Associate: " . ($deal->lead_associate ?: 'Ma. Lourdes T. Mata'),
                [
                    'user_id' => $adminUser?->id,
                    'user_name' => $owner,
                    'field_name' => 'lead_consultant',
                    'from_value' => 'Unassigned',
                    'to_value' => $deal->lead_consultant ?: 'John Kelly Abalde',
                ]
            );
            $h4->update(['created_at' => $createdAt->copy()->addDays(2), 'updated_at' => $createdAt->copy()->addDays(2)]);

            // 5. Stage transition to Consultation
            $h5 = $historyService->logActivity(
                $deal,
                DealHistory::TYPE_STAGE_CHANGED,
                "Stage transitioned from Qualification to Consultation.",
                [
                    'user_id' => $adminUser?->id,
                    'user_name' => $owner,
                    'from_stage' => 'Qualification',
                    'to_stage' => 'Consultation',
                    'field_name' => 'pipeline_stage',
                    'from_value' => 'Qualification',
                    'to_value' => 'Consultation',
                    'notes' => 'Detailed scope scoping session scheduled.',
                ]
            );
            $h5->update(['created_at' => $createdAt->copy()->addDays(3), 'updated_at' => $createdAt->copy()->addDays(3)]);

            // 6. Proposal Generated
            $h6 = $historyService->logActivity(
                $deal,
                DealHistory::TYPE_PROPOSAL_GENERATED,
                "Generated commercial proposal for \"{$deal->deal_title}\" with estimated value of ₱" . number_format($deal->amount ?: 150000, 2),
                [
                    'user_id' => $adminUser?->id,
                    'user_name' => $owner,
                    'document_name' => "Commercial Proposal {$deal->deal_code}",
                    'document_type' => 'PDF',
                    'from_stage' => 'Consultation',
                    'to_stage' => 'Proposal',
                    'new_values' => [
                        'proposal_code' => $deal->deal_code,
                        'total_amount' => $deal->amount ?: 150000,
                        'status' => 'Draft',
                    ],
                ]
            );
            $h6->update(['created_at' => $createdAt->copy()->addDays(4)->addHours(4), 'updated_at' => $createdAt->copy()->addDays(4)->addHours(4)]);

            // 7. If Deal is past Proposal stage, add transition
            if (in_array($deal->pipeline_stage, ['Proposal', 'Negotiation', 'Payment', 'Activation', 'Closed Won'], true)) {
                $h7 = $historyService->logActivity(
                    $deal,
                    DealHistory::TYPE_STAGE_CHANGED,
                    "Stage transitioned from Consultation to {$deal->pipeline_stage}.",
                    [
                        'user_id' => $adminUser?->id,
                        'user_name' => $owner,
                        'from_stage' => 'Consultation',
                        'to_stage' => $deal->pipeline_stage,
                        'field_name' => 'pipeline_stage',
                        'from_value' => 'Consultation',
                        'to_value' => $deal->pipeline_stage,
                        'notes' => 'Proposal reviewed and sent for client acceptance.',
                    ]
                );
                $h7->update(['created_at' => $createdAt->copy()->addDays(5), 'updated_at' => $createdAt->copy()->addDays(5)]);
            }
        }
    }
}
