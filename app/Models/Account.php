<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_code', 'account_type', 'account_name', 'company_id',
        'individual_contact_id', 'status',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function individualContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'individual_contact_id');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }
}
