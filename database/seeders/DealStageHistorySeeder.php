<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Deal;
use App\Models\DealHistory;
use App\Models\DealStageHistory;
use Carbon\Carbon;

class DealStageHistorySeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            'Inquiry',
            'Qualification',
            'Consultation',
            'Proposal',
            'Negotiation',
            'Payment',
            'Activation',
            'Closed Won',
            'Closed Lost',
        ];

        $deals = Deal::all();

        foreach ($deals as $deal) {
            $stageLogs = DealHistory::where('deal_id', $deal->id)
                ->where('activity_type', DealHistory::TYPE_STAGE_CHANGED)
                ->orderBy('created_at', 'asc')
                ->get();

            $currentStage = $deal->pipeline_stage ?: 'Inquiry';
            $dealCreatedAt = $deal->created_at ?? now()->subHours(4);

            if ($stageLogs->isNotEmpty()) {
                // Reconstruct sequential transitions
                $previousStage = 'Inquiry';
                $stageStartTime = $dealCreatedAt;

                foreach ($stageLogs as $log) {
                    $meta = $log->metadata ?? [];
                    $fromStage = $meta['from_stage'] ?? $meta['from_value'] ?? $previousStage;
                    $toStage = $meta['to_stage'] ?? $meta['to_value'] ?? $currentStage;
                    $stageEndTime = $log->created_at;

                    $durationSeconds = max(1, $stageStartTime->diffInSeconds($stageEndTime));
                    $durationFormatted = DealStageHistory::formatDuration($durationSeconds);

                    DealStageHistory::updateOrCreate(
                        [
                            'deal_id' => $deal->id,
                            'stage' => $fromStage,
                            'started_at' => $stageStartTime,
                        ],
                        [
                            'ended_at' => $stageEndTime,
                            'duration_seconds' => $durationSeconds,
                            'duration_formatted' => $durationFormatted,
                            'user_id' => $log->user_id,
                            'user_name' => $log->user_name ?? ($deal->owner_name ?? 'System'),
                            'notes' => $meta['notes'] ?? 'Stage transition recorded',
                        ]
                    );

                    $previousStage = $toStage;
                    $stageStartTime = $stageEndTime;
                }

                // Active current stage
                $deal->stage_entered_at = $stageStartTime;
                $deal->saveQuietly();

                $isClosed = in_array($currentStage, ['Closed Won', 'Closed Lost']);
                DealStageHistory::updateOrCreate(
                    [
                        'deal_id' => $deal->id,
                        'stage' => $currentStage,
                        'started_at' => $stageStartTime,
                    ],
                    [
                        'ended_at' => $isClosed ? now() : null,
                        'duration_seconds' => $isClosed ? max(1, $stageStartTime->diffInSeconds(now())) : null,
                        'duration_formatted' => $isClosed ? DealStageHistory::formatDuration(max(1, $stageStartTime->diffInSeconds(now()))) : null,
                        'user_id' => null,
                        'user_name' => $deal->owner_name ?? 'System',
                        'notes' => 'Current active stage',
                    ]
                );
            } else {
                // Single initial stage
                if (!$deal->stage_entered_at) {
                    $deal->stage_entered_at = $dealCreatedAt;
                    $deal->saveQuietly();
                }

                DealStageHistory::updateOrCreate(
                    [
                        'deal_id' => $deal->id,
                        'stage' => $currentStage,
                        'ended_at' => null,
                    ],
                    [
                        'started_at' => $deal->stage_entered_at ?? now(),
                        'user_id' => null,
                        'user_name' => $deal->owner_name ?? 'System',
                        'notes' => 'Initialized stage',
                    ]
                );
            }
        }
    }
}

