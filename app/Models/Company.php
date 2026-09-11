<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $table = 'companies';

    protected $fillable = [
        'company_code',
        'company_name',
        'industry',
        'address',
        'email',
        'phone',
        'status',
    ];

    /*
    |--------------------------------------------------------------------------
    | Contacts
    |--------------------------------------------------------------------------
    |
    | A Company can have multiple Contact master records.
    |
    */

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accounts
    |--------------------------------------------------------------------------
    |
    | A Company can be represented by one or more Business Accounts.
    |
    */

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}