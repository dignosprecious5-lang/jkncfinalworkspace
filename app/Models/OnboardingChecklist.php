<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingChecklist extends Model
{
    protected $fillable = [
        'personal_data_sheet_id',
        'employee_name',
        'employee_email',
        'position',
        'checked_documents',
        'docs_submitted',
        'docs_approved',
        'total_docs',
        'status',
        'upload_token',
        'submitted_at',
        'created_by',
    ];

    protected $casts = [
        'checked_documents' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function personalDataSheet()
    {
        return $this->belongsTo(PersonalDataSheet::class, 'personal_data_sheet_id');
    }
}
