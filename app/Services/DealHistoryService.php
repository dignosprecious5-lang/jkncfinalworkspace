<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\DealHistory;
use App\Models\DealStageHistory;
use App\Models\Proposal;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DealHistoryService
{
    /**
     * Pipeline stages in order
     */
    public const STAGES = [
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

    /**
     * Core logger for recording deal history.
     */
    public function logActivity(
        Deal $deal,
        string $activityType,
        string $description,
        array $options = []
    ): DealHistory {
        $user = Auth::user();
        $userId = $options['user_id'] ?? ($user ? $user->id : null);
        $userName = $options['user_name'] ?? ($user ? $user->name : ($deal->owner_name ?? 'System User'));

        $title = $options['title'] ?? match ($activityType) {
            DealHistory::TYPE_DEAL_CREATED => 'Deal Created',
            DealHistory::TYPE_STAGE_CHANGED => 'Stage Changed',
            DealHistory::TYPE_DEAL_UPDATED => 'Information Updated',
            DealHistory::TYPE_CONTACT_UPDATED => 'Contact Updated',
            DealHistory::TYPE_NOTE_ADDED => 'Note Added',
            DealHistory::TYPE_DOCUMENT_UPLOADED => 'Document Uploaded',
            DealHistory::TYPE_DOCUMENT_DELETED => 'Document Deleted',
            DealHistory::TYPE_PROPOSAL_GENERATED => 'Proposal Generated',
            DealHistory::TYPE_PROPOSAL_SENT => 'Proposal Sent',
            DealHistory::TYPE_PROPOSAL_ACCEPTED => 'Proposal Accepted',
            DealHistory::TYPE_PROPOSAL_REJECTED => 'Proposal Rejected',
            DealHistory::TYPE_ASSIGNMENT_CHANGED => 'Assignment Changed',
            default => ucwords(str_replace('_', ' ', $activityType)),
        };

        $metadata = array_merge($options, [
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'System',
        ]);

        return DealHistory::create([
            'deal_id' => $deal->id,
            'user_id' => $userId,
            'user_name' => $userName,
            'activity_type' => $activityType,
            'title' => $title,
            'description' => $description,
            'old_stage' => $options['from_stage'] ?? null,
            'new_stage' => $options['to_stage'] ?? null,
            'old_value' => isset($options['from_value']) ? (is_array($options['from_value']) ? json_encode($options['from_value']) : (string)$options['from_value']) : null,
            'new_value' => isset($options['to_value']) ? (is_array($options['to_value']) ? json_encode($options['to_value']) : (string)$options['to_value']) : null,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log initial deal creation and initialize persistent stage start.
     */
    public function logDealCreated(Deal $deal, ?int $userId = null, ?string $userName = null): DealHistory
    {
        $initialStage = $deal->pipeline_stage ?? 'Inquiry';
        $actorName = $userName ?? (auth()->user()?->name ?? ($deal->owner_name ?? 'System User'));
        $actorId = $userId ?? auth()->id();

        // Initialize stage start timestamp on deal
        if (!$deal->stage_entered_at) {
            $deal->stage_entered_at = $deal->created_at ?? now();
            $deal->saveQuietly();
        }

        // Initialize persistent stage record
        DealStageHistory::firstOrCreate(
            [
                'deal_id' => $deal->id,
                'stage' => $initialStage,
                'ended_at' => null,
            ],
            [
                'started_at' => $deal->stage_entered_at ?? now(),
                'user_id' => $actorId,
                'user_name' => $actorName,
                'notes' => 'Initial deal creation stage',
            ]
        );

        return $this->logActivity(
            $deal,
            DealHistory::TYPE_DEAL_CREATED,
            "Deal \"{$deal->deal_title}\" was created by {$actorName} with code {$deal->deal_code}.",
            [
                'user_id' => $actorId,
                'user_name' => $actorName,
                'to_stage' => $initialStage,
                'new_values' => [
                    'deal_title' => $deal->deal_title,
                    'deal_code' => $deal->deal_code,
                    'engagement_type' => $deal->engagement_type,
                    'amount' => $deal->amount,
                    'pipeline_stage' => $initialStage,
                ],
            ]
        );
    }

    /**
     * Log pipeline or commercial stage change with persistent duration recording.
     */
    public function logStageChanged(
        Deal $deal,
        string $fromStage,
        string $toStage,
        ?string $reason = null,
        ?int $userId = null
    ): DealHistory {
        $actorId = $userId ?? auth()->id();
        $actorName = auth()->user()?->name ?? ($deal->owner_name ?? 'System User');
        $now = now();

        $previousDurationSeconds = null;
        $previousDurationFormatted = null;

        // 1. Close the previous active stage in DealStageHistory
        $activePrevStage = DealStageHistory::where('deal_id', $deal->id)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        if ($activePrevStage) {
            $activePrevStage->ended_at = $now;
            $start = $activePrevStage->started_at ?? ($deal->stage_entered_at ?? $deal->created_at ?? $now);
            $previousDurationSeconds = max(0, $start->diffInSeconds($now));
            $previousDurationFormatted = DealStageHistory::formatDuration($previousDurationSeconds);

            $activePrevStage->duration_seconds = $previousDurationSeconds;
            $activePrevStage->duration_formatted = $previousDurationFormatted;
            $activePrevStage->save();
        } else {
            // If no previous open record existed, create a completed one for accuracy
            $start = $deal->stage_entered_at ?? ($deal->created_at ?? $now);
            $previousDurationSeconds = max(0, $start->diffInSeconds($now));
            $previousDurationFormatted = DealStageHistory::formatDuration($previousDurationSeconds);

            DealStageHistory::create([
                'deal_id' => $deal->id,
                'stage' => $fromStage,
                'started_at' => $start,
                'ended_at' => $now,
                'duration_seconds' => $previousDurationSeconds,
                'duration_formatted' => $previousDurationFormatted,
                'user_id' => $actorId,
                'user_name' => $actorName,
                'notes' => 'Completed stage transition',
            ]);
        }

        // 2. Update stage_entered_at on Deal
        $deal->stage_entered_at = $now;
        if ($deal->pipeline_stage !== $toStage) {
            $deal->pipeline_stage = $toStage;
        }
        $deal->saveQuietly();

        // 3. Start a new live timer for the new stage
        DealStageHistory::create([
            'deal_id' => $deal->id,
            'stage' => $toStage,
            'started_at' => $now,
            'ended_at' => null,
            'user_id' => $actorId,
            'user_name' => $actorName,
            'notes' => $reason,
        ]);

        $desc = "Stage transitioned from {$fromStage} to {$toStage}.";
        if ($previousDurationFormatted) {
            $desc .= " (Duration in {$fromStage}: {$previousDurationFormatted})";
        }
        if ($reason) {
            $desc .= " Note: {$reason}";
        }

        return $this->logActivity(
            $deal,
            DealHistory::TYPE_STAGE_CHANGED,
            $desc,
            [
                'user_id' => $actorId,
                'user_name' => $actorName,
                'from_stage' => $fromStage,
                'to_stage' => $toStage,
                'field_name' => 'pipeline_stage',
                'from_value' => $fromStage,
                'to_value' => $toStage,
                'previous_stage_duration' => $previousDurationFormatted,
                'previous_stage_duration_seconds' => $previousDurationSeconds,
                'notes' => $reason,
                'old_values' => ['pipeline_stage' => $fromStage],
                'new_values' => ['pipeline_stage' => $toStage],
            ]
        );
    }

    /**
     * Log field updates on deal.
     */
    public function logDealUpdated(
        Deal $deal,
        array $dirtyAttributes,
        array $originalAttributes,
        ?int $userId = null
    ): ?DealHistory {
        $ignored = ['updated_at', 'created_at', 'id'];
        $changesOld = [];
        $changesNew = [];
        $fieldNames = [];

        foreach ($dirtyAttributes as $key => $newVal) {
            if (in_array($key, $ignored, true)) {
                continue;
            }
            $oldVal = $originalAttributes[$key] ?? null;
            if ($oldVal != $newVal) {
                $changesOld[$key] = $oldVal;
                $changesNew[$key] = $newVal;
                $fieldNames[] = ucwords(str_replace('_', ' ', $key));
            }
        }

        if (empty($changesNew)) {
            return null;
        }

        $fieldsStr = implode(', ', array_slice($fieldNames, 0, 4));
        if (count($fieldNames) > 4) {
            $fieldsStr .= ' and ' . (count($fieldNames) - 4) . ' more fields';
        }

        return $this->logActivity(
            $deal,
            DealHistory::TYPE_DEAL_UPDATED,
            "Updated deal details: {$fieldsStr}.",
            [
                'user_id' => $userId,
                'field_name' => implode(', ', $fieldNames),
                'old_values' => $changesOld,
                'new_values' => $changesNew,
            ]
        );
    }

    /**
     * Log proposal creation.
     */
    public function logProposalGenerated(Deal $deal, Proposal $proposal, ?int $userId = null): DealHistory
    {
        return $this->logActivity(
            $deal,
            DealHistory::TYPE_PROPOSAL_GENERATED,
            "Generated commercial proposal \"{$proposal->proposal_code}\" with total value of ₱" . number_format($proposal->total_amount ?? 0, 2) . ".",
            [
                'user_id' => $userId,
                'proposal_id' => $proposal->id,
                'document_name' => "Proposal {$proposal->proposal_code}",
                'document_type' => 'PDF / Formal Offer',
                'new_values' => [
                    'proposal_code' => $proposal->proposal_code,
                    'total_amount' => $proposal->total_amount,
                    'status' => $proposal->status ?? 'Draft',
                ],
            ]
        );
    }

    /**
     * Log note addition.
     */
    public function logNoteAdded(Deal $deal, string $noteContent, ?string $category = 'General', ?int $userId = null): DealHistory
    {
        $snippet = mb_strimwidth($noteContent, 0, 80, '...');
        return $this->logActivity(
            $deal,
            DealHistory::TYPE_NOTE_ADDED,
            "Added a {$category} note: \"{$snippet}\"",
            [
                'user_id' => $userId,
                'notes' => $noteContent,
                'new_values' => ['category' => $category, 'content' => $noteContent],
            ]
        );
    }

    /**
     * Log document upload.
     */
    public function logDocumentUploaded(Deal $deal, string $documentName, string $documentType = 'Attachment', ?int $userId = null): DealHistory
    {
        return $this->logActivity(
            $deal,
            DealHistory::TYPE_DOCUMENT_UPLOADED,
            "Uploaded document \"{$documentName}\" ({$documentType}).",
            [
                'user_id' => $userId,
                'document_name' => $documentName,
                'document_type' => $documentType,
            ]
        );
    }

    /**
     * Log assignment change.
     */
    public function logAssignmentChanged(Deal $deal, string $role, ?string $fromUser, ?string $toUser, ?int $userId = null): DealHistory
    {
        return $this->logActivity(
            $deal,
            DealHistory::TYPE_ASSIGNMENT_CHANGED,
            "Reassigned {$role} from " . ($fromUser ?: 'None') . " to " . ($toUser ?: 'None') . ".",
            [
                'user_id' => $userId,
                'field_name' => $role,
                'from_value' => $fromUser,
                'to_value' => $toUser,
            ]
        );
    }

    /**
     * Compute Stage Progression summary based on Deal pipeline history and current stage.
     */
    public function getStageProgression(Deal $deal): array
    {
        $currentStage = $deal->pipeline_stage ?? 'Inquiry';
        $allStages = self::STAGES;

        $currentIndex = array_search($currentStage, $allStages, true);
        if ($currentIndex === false) {
            $currentIndex = 0;
        }

        $stageHistories = DealHistory::where('deal_id', $deal->id)
            ->whereIn('activity_type', [DealHistory::TYPE_STAGE_CHANGED, DealHistory::TYPE_DEAL_CREATED])
            ->orderBy('created_at', 'asc')
            ->get();

        $stageData = [];
        foreach ($allStages as $index => $stageName) {
            $historyEntry = $stageHistories->first(function ($h) use ($stageName) {
                return ($h->new_stage === $stageName) || ($h->to_stage === $stageName) || ($h->activity_type === DealHistory::TYPE_DEAL_CREATED && ($h->new_stage === $stageName || $h->to_stage === $stageName));
            });

            if ($stageName === $currentStage) {
                $status = 'current';
            } elseif ($index < $currentIndex) {
                $status = 'completed';
            } else {
                $status = 'not_started';
            }

            $completedAt = null;
            $completedBy = null;

            if ($historyEntry) {
                $completedAt = $historyEntry->created_at ? $historyEntry->created_at->format('M d, Y · g:i A') : null;
                $completedBy = $historyEntry->user_name;
            } elseif ($status === 'completed' || $status === 'current') {
                $completedAt = $deal->created_at ? $deal->created_at->format('M d, Y') : null;
                $completedBy = $deal->owner_name ?? 'System';
            }

            $stageRecords = DealStageHistory::where('deal_id', $deal->id)->where('stage', $stageName)->get();
            $durationFormatted = null;

            if ($status === 'current') {
                $running = $stageRecords->firstWhere('ended_at', null);
                if ($running) {
                    $durationFormatted = $running->formatted_duration;
                } else {
                    $durationFormatted = DealStageHistory::formatDuration(max(0, $deal->current_stage_started_at->diffInSeconds(now())));
                }
            } elseif ($status === 'completed') {
                $completed = $stageRecords->whereNotNull('ended_at')->last();
                if ($completed && !empty($completed->duration_formatted)) {
                    $durationFormatted = $completed->duration_formatted;
                } elseif ($completed && $completed->duration_seconds !== null) {
                    $durationFormatted = DealStageHistory::formatDuration($completed->duration_seconds);
                }
            }

            $stageData[] = [
                'name' => $stageName,
                'status' => $status,
                'duration' => $durationFormatted,
                'completed_at' => $completedAt,
                'completed_by' => $completedBy,
                'is_current' => ($status === 'current'),
                'is_completed' => ($status === 'completed'),
                'is_not_started' => ($status === 'not_started'),
            ];
        }

        return $stageData;
    }

    /**
     * Compute activity count metrics for the right sidebar.
     */
    public function getActivityCounts(Deal $deal): array
    {
        $baseQuery = DealHistory::where('deal_id', $deal->id);

        $total = (clone $baseQuery)->count();
        $stageChanges = (clone $baseQuery)->where('activity_type', DealHistory::TYPE_STAGE_CHANGED)->count();
        $notes = (clone $baseQuery)->where('activity_type', DealHistory::TYPE_NOTE_ADDED)->count();
        $documents = (clone $baseQuery)->whereIn('activity_type', [DealHistory::TYPE_DOCUMENT_UPLOADED, DealHistory::TYPE_DOCUMENT_DELETED])->count();
        $proposals = (clone $baseQuery)->whereIn('activity_type', [DealHistory::TYPE_PROPOSAL_GENERATED, DealHistory::TYPE_PROPOSAL_SENT, DealHistory::TYPE_PROPOSAL_ACCEPTED, DealHistory::TYPE_PROPOSAL_REJECTED])->count();
        $updates = (clone $baseQuery)->whereIn('activity_type', [DealHistory::TYPE_DEAL_UPDATED, DealHistory::TYPE_CONTACT_UPDATED, DealHistory::TYPE_ASSIGNMENT_CHANGED])->count();

        return [
            'total' => $total,
            'stage_changes' => $stageChanges,
            'notes' => $notes,
            'documents' => $documents,
            'proposals' => $proposals,
            'updates' => $updates,
        ];
    }
}
