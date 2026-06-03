<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Correspondence extends Model
{
    protected $table = 'correspondences';

    /*
     * Backward compatible:
     * - Keeps your old fields: uploaded_date, user, submitted_by, sender_type, sender, department, details, date, time
     * - Adds the new Town Hall-style correspondence fields: ref_no, company_name, registration_number, principal_address,
     *   to_for, from_name, body, Level 1 / Level 2 approver fields, archive fields, etc.
     */
    protected $fillable = [
        // New correspondence/Town Hall-style fields
        'ref_no',
        'correspondence_date',
        'type',
        'company_name',
        'registration_number',
        'principal_address',
        'to_for',
        'from_name',
        'department_stakeholder',
        'body',
        'cc',
        'additional',
        'status',
        'posted_at',
        'posted_by',

        // Level 1 approver - Employee Profile
        'management_approver_id',
        'management_approver_user_id',
        'management_approver_name',
        'management_approver_position',
        'management_approver_department',
        'management_approval_status',
        'management_approved_at',

        // Level 2 approver - GIS Directors/Officers
        'executive_approver_id',
        'executive_approver_user_id',
        'executive_approver_name',
        'executive_approver_position',
        'executive_approver_department',
        'executive_approval_status',
        'executive_approved_at',

        // Archive
        'is_archived',
        'archived_at',

        // Existing/old fields from your current model
        'uploaded_date',
        'user',
        'submitted_by',
        'tin',
        'subject',
        'sender_type',
        'sender',
        'department',
        'details',
        'date',
        'time',
        'deadline',
        'sent_via',
        'workflow_status',
        'approval_status',
        'approved_by',
        'approved_at',
        'review_note',
        'created_by',
        'attachment',
    ];

    protected $appends = ['computed_status'];

    protected $casts = [
        // New fields
        'correspondence_date' => 'date:Y-m-d',
        'posted_at' => 'datetime',
        'management_approved_at' => 'datetime',
        'executive_approved_at' => 'datetime',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',

        // Existing/old fields
        'uploaded_date' => 'date:Y-m-d',
        'date' => 'date:Y-m-d',
        'deadline' => 'date:Y-m-d',
        'approved_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function getComputedStatusAttribute()
    {
        if (!$this->deadline) {
            return 'Open';
        }

        return $this->deadline->lt(Carbon::today()) ? 'Closed' : 'Open';
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')
            ->withDefault([
                'name' => $this->user ?: 'System',
            ]);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
