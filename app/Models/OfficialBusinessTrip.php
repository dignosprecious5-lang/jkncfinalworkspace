<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficialBusinessTrip extends Model
{
    protected $fillable = [
        'ob_reference_no',

        'employee_id',
        'employee_code',
        'employee_name',
        'position',
        'department',
        'immediate_superior',
        'superior_email',

        'destination',
        'additional_stops',
        'purpose',
        'purpose_options',
        'purpose_other',
        'trip_type',
        'date_from',
        'date_to',
        'departure_time',
        'return_time',
        'nature_of_travel',
        'team_members',

        'is_client_travel',
        'client_type',
        'client_id_no',
        'client_name',
        'client_email',
        'client_contract_number',
        'contract_type',
        'travel_billability',

        'client_payment_status',
        'client_payment_items',
        'client_payment_other',
        'amount_client_will_pay',

        'travel_credit_details',

        'transportation_mode',
        'transportation_details',
        'estimated_expenses',

        'attachment_paths',
        'attachment_types',
        'attachment_other',

        'remarks',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'estimated_expenses' => 'decimal:2',
        'amount_client_will_pay' => 'decimal:2',

        'purpose_options' => 'array',
        'team_members' => 'array',
        'client_payment_items' => 'array',
        'travel_credit_details' => 'array',
        'transportation_details' => 'array',
        'attachment_paths' => 'array',
        'attachment_types' => 'array',
        'is_client_travel' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}