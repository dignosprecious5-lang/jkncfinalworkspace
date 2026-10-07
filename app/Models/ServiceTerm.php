<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_version_id',
        'title',
        'content',
        'sort_order',
        'status',
        'scope', // Idinagdag natin ito
    ];

    public function serviceVersion(): BelongsTo
    {
        return $this->belongsTo(ServiceVersion::class);
    }
}