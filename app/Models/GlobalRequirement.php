<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\GlobalRequirement;

class GlobalRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'requirement_name',
        'client_type',
        'is_mandatory',
        'source',
        'file_required',
        'instructions',
        'validity_expiration',
        'document_name',
        'description',
        'status',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
    ];
}