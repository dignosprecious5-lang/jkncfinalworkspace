<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'business_name',
        'record_unit_allocation',
    ];

    protected function casts(): array
    {
        return [
            'record_unit_allocation' => 'integer',
            // The Record Numbering settings. Cast because the generator reads
            // them on every filing and `record_include_year` decides whether a
            // year is stamped into the number — a raw 0/1 would be truthy when
            // the year is switched off.
            'record_sequence_digits' => 'integer',
            'record_next_sequence' => 'integer',
            'record_include_year' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function intakeItems(): HasMany
    {
        return $this->hasMany(RecordIntakeItem::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(RecordFile::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(RecordSource::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(Record::class);
    }
}
