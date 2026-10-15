<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EngagementPeriod extends Model
{
    use HasFactory;

    protected $table = 'engagement_periods';

    protected $fillable = [
        'engagement_id',
        'service_version_id',
        'period_name',
        'period_start_date',
        'period_end_date',
        'status',
        'generated_at',
    ];

    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'generated_at' => 'datetime',
    ];

    /**
     * An engagement period belongs to an engagement.
     */
    public function engagement(): BelongsTo
    {
        return $this->belongsTo(Engagement::class);
    }

    /**
     * An engagement period belongs to a service version specification.
     */
    public function serviceVersion(): BelongsTo
    {
        return $this->belongsTo(ServiceVersion::class);
    }

    /**
     * An engagement period has many operational tasks.
     */
    public function operationalTasks(): HasMany
    {
        return $this->hasMany(OperationalTask::class, 'engagement_period_id');
    }
}