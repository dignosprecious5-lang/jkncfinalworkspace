<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Project extends Model
{
    protected $fillable = [
        'project_code',
        'deal_id',
        'contact_id',
        'company_id',
        'name',
        'engagement_type',
        'status',
        'current_phase',
        'current_step',
        'planned_start_date',
        'target_completion_date',
        'client_preferred_completion_date',
        'assigned_project_manager',
        'assigned_consultant',
        'assigned_associate',
        'client_name',
        'business_name',
        'service_area',
        'services',
        'products',
        'deal_value',
        'scope_summary',
        'client_confirmation_name',
        'metadata',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'planned_start_date' => 'date',
        'target_completion_date' => 'date',
        'client_preferred_completion_date' => 'date',
        'deal_value' => 'decimal:2',
        'metadata' => 'array',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Project $project): void {
            if (blank($project->project_code)) {
                $project->project_code = static::generateNextProjectCode();
            }
        });
    }

    public static function generateNextProjectCode(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $prefix = sprintf('PROJ-%d-', $year);

        $nextNumber = DB::transaction(function () use ($prefix): int {
            $latestCode = static::query()
                ->where('project_code', 'like', $prefix.'%')
                ->orderByDesc('project_code')
                ->lockForUpdate()
                ->value('project_code');

            if (! is_string($latestCode) || ! preg_match('/^PROJ-\d{4}-\d{3}$/', $latestCode)) {
                return 1;
            }

            return ((int) substr($latestCode, -3)) + 1;
        });

        return $prefix.str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function starts(): HasMany
    {
        return $this->hasMany(ProjectStart::class);
    }

    public function sows(): HasMany
    {
        return $this->hasMany(ProjectSow::class);
    }

    public function sowReports(): HasMany
    {
        return $this->hasMany(ProjectSowReport::class);
    }

    public function ntps(): HasMany
    {
        return $this->hasMany(ProjectNtp::class);
    }

    /**
     * Returns true when this workspace is a shell created at Closed Won
     * and is still awaiting START form Admin Approval before full activation.
     */
    public function isShell(): bool
    {
        return (bool) data_get($this->metadata, 'is_shell', false);
    }
}
