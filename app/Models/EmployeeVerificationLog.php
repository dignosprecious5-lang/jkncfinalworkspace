<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeVerificationLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
