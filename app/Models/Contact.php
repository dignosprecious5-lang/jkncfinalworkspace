<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory;

    protected $table = 'contacts';

    protected $fillable = [
        'contact_code',
        'contact_type',
        'salutation',
        'first_name',
        'middle_name',
        'last_name',
        'name_extension',
        'date_of_birth',
        'sex',
        'email',
        'mobile_number',
        'address',
        'company_id',
        'position',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date:Y-m-d',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Company
    |--------------------------------------------------------------------------
    |
    | For a Business Account, this Contact may belong to the
    | selected Company master record.
    |
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accounts
    |--------------------------------------------------------------------------
    |
    | A Contact may be used as the Individual Contact for an Account.
    |
    */

    public function accounts(): HasMany
    {
        return $this->hasMany(
            Account::class,
            'individual_contact_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Deal Contacts
    |--------------------------------------------------------------------------
    |
    | This connects the master Contact to Deal-specific contact records.
    |
    */

    public function dealContacts(): HasMany
    {
        return $this->hasMany(DealContact::class);
    }
}