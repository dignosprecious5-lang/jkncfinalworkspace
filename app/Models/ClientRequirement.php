<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientRequirement extends Model
{
    use HasFactory;

    /**
     * Pinapayagan ang lahat ng kinakailangang columns kasama ang proposal_id at submitted_at
     */
    protected $fillable = [
        'proposal_id',
        'service_id',
        'user_id',
        'document_name',
        'description',
        'client_type',
        'source',
        'is_mandatory',
        'file_required',
        'original_required',
        'conditional_rule',
        'status',
        'file_path',
        'rejection_reason',
        'submitted_at',
    ];

    /**
     * Relationship sa Proposal
     */
    public function proposal()
    {
        return $this->belongsTo(Proposal::class, 'proposal_id');
    }

    /**
     * Relationship sa Service
     */
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    /**
     * Relationship sa User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}