<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectScopeItem extends Model
{
    use HasFactory;

    protected $table = 'project_scope_items';

    protected $fillable = [
        'deal_id',
        'scope_type',
        'main_task',
        'sub_task',
        'responsible',
        'duration',
        'start_date',
        'end_date',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'duration' => 'integer',
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
        ];
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }
}