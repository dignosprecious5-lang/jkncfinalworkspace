<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceEvaluation extends Model
{
    protected $fillable = [
        'employee_id',
        'employee_name',
        'position',
        'department',
        'supervisor_name',
        'evaluation_period_start',
        'evaluation_period_end',
        'evaluation_date',
        'quality_of_work',
        'timeliness_compliance',
        'productivity_output',
        'attendance_punctuality',
        'policy_compliance',
        'task_ownership',
        'communication_coordination',
        'teamwork_conduct',
        'initiative_problem_solving',
        'adaptability_learning',
        'client_support',
        'care_of_resources',
        'quality_of_work_remarks',
        'timeliness_compliance_remarks',
        'productivity_output_remarks',
        'attendance_punctuality_remarks',
        'policy_compliance_remarks',
        'task_ownership_remarks',
        'communication_coordination_remarks',
        'teamwork_conduct_remarks',
        'initiative_problem_solving_remarks',
        'adaptability_learning_remarks',
        'client_support_remarks',
        'care_of_resources_remarks',
        'total_score',
        'average_rating',
        'overall_performance_rating',
        'key_strengths',
        'areas_for_improvement',
        'action_plan',
        'employee_comments',
        'supervisor_recommendations',
        'evaluated_by_name',
        'evaluated_by_position',
        'evaluated_by_date',
        'reviewed_by_name',
        'reviewed_by_position',
        'reviewed_by_date',
        'employee_acknowledgment_date',
        'created_by',
    ];

    protected $casts = [
        'evaluation_date' => 'date',
        'evaluation_period_start' => 'date',
        'evaluation_period_end' => 'date',
        'evaluated_by_date' => 'date',
        'reviewed_by_date' => 'date',
        'employee_acknowledgment_date' => 'date',
        'supervisor_recommendations' => 'array',
        'action_plan' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function calculateTotalScore(): int
    {
        $ratings = [
            $this->quality_of_work,
            $this->timeliness_compliance,
            $this->productivity_output,
            $this->attendance_punctuality,
            $this->policy_compliance,
            $this->task_ownership,
            $this->communication_coordination,
            $this->teamwork_conduct,
            $this->initiative_problem_solving,
            $this->adaptability_learning,
            $this->client_support,
            $this->care_of_resources,
        ];

        $validRatings = array_filter($ratings, fn($r) => $r !== null);
        return array_sum($validRatings);
    }

    public function calculateAverageRating(): float
    {
        $ratings = [
            $this->quality_of_work,
            $this->timeliness_compliance,
            $this->productivity_output,
            $this->attendance_punctuality,
            $this->policy_compliance,
            $this->task_ownership,
            $this->communication_coordination,
            $this->teamwork_conduct,
            $this->initiative_problem_solving,
            $this->adaptability_learning,
            $this->client_support,
            $this->care_of_resources,
        ];

        $validRatings = array_filter($ratings, fn($r) => $r !== null);
        $count = count($validRatings);

        return $count > 0 ? round(array_sum($validRatings) / $count, 2) : 0;
    }

    public function getOverallRating(): string
    {
        $avg = $this->average_rating ?? $this->calculateAverageRating();

        if ($avg >= 4.50) return 'Excellent';
        if ($avg >= 3.50) return 'Very Good';
        if ($avg >= 2.50) return 'Satisfactory';
        if ($avg >= 1.50) return 'Needs Improvement';
        return 'Unsatisfactory';
    }
}
