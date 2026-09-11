<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequirement extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Relationship to Service model (fixes RelationNotFoundException)
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    /**
     * Relationship to ServiceVersion model
     */
    public function serviceVersion(): BelongsTo
    {
        return $this->belongsTo(ServiceVersion::class, 'service_version_id');
    }
}