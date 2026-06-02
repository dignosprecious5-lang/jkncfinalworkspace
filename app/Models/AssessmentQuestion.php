<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentQuestion extends Model
{
    protected $fillable = [
        'assessment_type_id',
        'question',
        'choice_a',
        'choice_b',
        'choice_c',
        'choice_d',
        'correct_answer',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'correct_answer' => 'integer',
        'sort_order' => 'integer',
    ];

    public function assessmentType()
    {
        return $this->belongsTo(AssessmentType::class);
    }

    public function getChoicesArrayAttribute(): array
    {
        return [
            $this->choice_a,
            $this->choice_b,
            $this->choice_c,
            $this->choice_d,
        ];
    }
}
