<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function questions()
    {
        return $this->hasMany(AssessmentQuestion::class);
    }
}
