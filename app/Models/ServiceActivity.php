<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceActivity extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ServiceActivity::class, 'parent_id');
    }

    public function subActivities(): HasMany
    {
        return $this->hasMany(ServiceActivity::class, 'parent_id')->orderBy('sequence');
    }
}