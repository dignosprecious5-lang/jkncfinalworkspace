<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_recurring' => 'boolean',
        'rate_per_unit' => 'decimal:2',
        'max_cap' => 'decimal:2',
        'price_fee' => 'decimal:2',
        'cost_of_service' => 'decimal:2',
        'deadline' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'effective_date' => 'date',
        'service_area' => 'array',
        'requirements' => 'array',
        'engagement_structure' => 'array',
        'custom_field_values' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Service $service) {
            $hasLegacyId = \Illuminate\Support\Facades\Schema::hasColumn('services', 'service_id');

            if ($hasLegacyId && empty($service->attributes['service_id'])) {
                do {
                    $candidateId = (string) mt_rand(10000, 99999);
                } while (static::where('service_id', $candidateId)->exists());
                $service->service_id = $candidateId;
            }

            $rawName = $service->attributes['name'] ?? null;
            $rawLegacyName = $service->attributes['service_name'] ?? null;

            if (\Illuminate\Support\Facades\Schema::hasColumn('services', 'service_name') && empty($rawLegacyName) && ! empty($rawName)) {
                $service->service_name = $rawName;
            } elseif (\Illuminate\Support\Facades\Schema::hasColumn('services', 'name') && empty($rawName) && ! empty($rawLegacyName)) {
                $service->name = $rawLegacyName;
            }

            $resolvedName = $service->attributes['name'] ?? $service->attributes['service_name'] ?? 'Service';

            if (\Illuminate\Support\Facades\Schema::hasColumn('services', 'service_area') && empty($service->attributes['service_area'])) {
                $service->service_area = ['General'];
            }

            if (\Illuminate\Support\Facades\Schema::hasColumn('services', 'service_description') && empty($service->attributes['service_description'])) {
                $service->service_description = $service->attributes['short_description'] ?? $resolvedName;
            }

            if (\Illuminate\Support\Facades\Schema::hasColumn('services', 'service_activity_output') && empty($service->attributes['service_activity_output'])) {
                $service->service_activity_output = $service->attributes['short_description'] ?? $resolvedName;
            }

            if (\Illuminate\Support\Facades\Schema::hasColumn('services', 'engagement_structure') && empty($service->attributes['engagement_structure'])) {
                $behavior = strtolower((string) ($service->attributes['engagement_behavior'] ?? 'project'));
                $structure = $behavior === 'regular'
                    ? ['Regular (Retainer) Engagement']
                    : ($behavior === 'both' || $behavior === 'hybrid'
                        ? ['Project Engagement', 'Regular (Retainer) Engagement']
                        : ['Project Engagement']);
                $service->engagement_structure = $structure;
            }
        });

        static::updating(function (Service $service) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('services', 'service_name') && $service->isDirty('name') && filled($service->attributes['name'] ?? null)) {
                $service->service_name = $service->attributes['name'];
            }
        });
    }

    /**
     * Name accessors for compatibility
     */
    public function getNameAttribute(?string $value): ?string
    {
        return $value ?? $this->attributes['service_name'] ?? null;
    }

    public function getServiceNameAttribute(?string $value): ?string
    {
        return $value ?? $this->attributes['name'] ?? null;
    }

    // =========================================================
    // RELATIONSHIPS FROM MAIN
    // =========================================================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // =========================================================
    // RELATIONSHIPS FROM FEATURE BRANCH
    // =========================================================

    public function serviceArea(): BelongsTo
    {
        return $this->belongsTo(ServiceArea::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ServiceVersion::class);
    }

    public function activeVersion(): BelongsTo
    {
        return $this->belongsTo(ServiceVersion::class, 'active_version_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ServiceAuditLog::class)->latest();
    }
}
