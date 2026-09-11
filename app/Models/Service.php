<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Relationship para sa lahat ng bersyon ng serbisyo.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ServiceVersion::class);
    }

    /**
     * Relationship para sa kasalukuyang active version.
     */
    public function activeVersion(): BelongsTo
    {
        return $this->belongsTo(ServiceVersion::class, 'active_version_id');
    }

    /**
     * Relationship para sa Service Audit Logs (Section 7).
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(ServiceAuditLog::class)->latest();
    }

    /*
    |--------------------------------------------------------------------------
    | FUTURE RELATIONS
    |--------------------------------------------------------------------------
    | I-uncomment lamang ang mga sumusunod na methods kapag nagawa mo na
    | ang 'Contract' at 'Proposal' models sa iyong app/Models directory.
    |
    | public function contracts(): HasMany
    | {
    |     return $this->hasMany(Contract::class);
    | }
    |
    | public function proposals(): HasMany
    | {
    |     return $this->hasMany(Proposal::class);
    | }
    |
    */
}