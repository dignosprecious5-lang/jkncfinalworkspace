<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Training extends Model
{
    protected $fillable = [
        'title',
        'description',
        'provider',
        'duration_value',
        'duration_unit',
    ];

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute()
    {
        if ($this->duration_value && $this->duration_unit) {
            return $this->duration_value . ' ' . $this->duration_unit;
        }

        return null;
    }

    /**
     * Training assignments
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TrainingAssignment::class);
    }
}