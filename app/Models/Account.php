<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $table = 'accounts';

    protected $fillable = [
        'account_code',
        'account_type',
        'account_name',
        'company_id',
        'individual_contact_id',
        'status',
    ];

    /*
    |--------------------------------------------------------------------------
    | Company
    |--------------------------------------------------------------------------
    |
    | Business Account → Company master record
    |
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Individual Contact
    |--------------------------------------------------------------------------
    |
    | Individual Account → Individual Contact master record
    |
    */

    public function individualContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'individual_contact_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Deals
    |--------------------------------------------------------------------------
    |
    | One Account can have multiple Deals.
    |
    */

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }
}