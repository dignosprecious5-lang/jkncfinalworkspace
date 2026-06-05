<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Correspondence extends Model
{
    protected $table = 'correspondences';

    protected $fillable = [
        // New correspondence/Town Hall-style fields
        'ref_no',
        'correspondence_date',
        'type',
        'company_name',
        'registration_number',
        'principal_address',
        'to_for_label',
        'to_for',
        'from_name',

        // Input-based Prepared By signature fields
        'prepared_by_name',
        'prepared_by_position',
        'prepared_by_department',
        'prepared_on',

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
        'management_approver_email',
        'management_approver_position',
        'management_approver_department',

        // Input-based Level 1 signature fields
        'management_signature_name',
        'management_signature_position',
        'management_signature_department',

        'management_approval_status',
        'management_approved_at',
        'management_approved_on',

        // Level 2 approver - GIS Directors/Officers
        'executive_approver_id',
        'executive_approver_user_id',
        'executive_approver_name',
        'executive_approver_email',
        'executive_approver_position',
        'executive_approver_department',

        // Input-based Level 2 signature fields
        'executive_signature_name',
        'executive_signature_position',
        'executive_signature_department',

        'executive_approval_status',
        'executive_approved_at',
        'executive_approved_on',

        // Archive
        'is_archived',
        'archived_at',

        // Existing/old fields
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
        'submitted_at',
    ];

    protected $appends = ['computed_status'];

    protected $casts = [
        // New fields
        'correspondence_date' => 'date:Y-m-d',
        'posted_at' => 'datetime',

        // Input-based prepared by / approval dates
        'prepared_on' => 'datetime',
        'management_approved_at' => 'datetime',
        'management_approved_on' => 'datetime',
        'executive_approved_at' => 'datetime',
        'executive_approved_on' => 'datetime',

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
