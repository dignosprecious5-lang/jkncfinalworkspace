<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Engagement extends Model
{
    use HasFactory;

    protected $table = 'engagements';

    protected $fillable = [
        'service_id',
        'service_version_id', // Idinagdag natin dito para ligtas
        'status',
    ];

    public function serviceVersion(): BelongsTo
    {
        return $this->belongsTo(ServiceVersion::class, 'service_version_id');
    }

    public function engagementPeriods(): HasMany
    {
        return $this->hasMany(EngagementPeriod::class, 'engagement_id');
    }
}