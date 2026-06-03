<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $fillable = [
        'role',
        'permission',

        'manage_users',
        'access_admin_dashboard',

        'create_townhall',
        'approve_townhall',

        'create_corporate',
        'approve_corporate',

        'access_townhall',
        'access_corporate',
        'access_activities',
        'access_contacts',
        'access_company',

        'access_transmittal',
        'access_deals',
        'access_services',
        'access_project',
        'access_regular',
        'access_product',
        'access_policies',

        'create_sales_marketing',
        'approve_sales_marketing',
        'access_sales_marketing',
        'approve_policies',

        'access_human_capital',

        'access_finance',
        'create_finance',
        'approve_finance',
        'access_finance_supplier',
        'access_finance_service',
        'access_finance_product',
        'access_finance_chart_account',
        'access_finance_bank_account',
        'access_finance_pr',
        'access_finance_po',
        'access_finance_ca',
        'access_finance_lr',
        'access_finance_err',
        'access_finance_dv',
        'access_finance_pda',
        'access_finance_crf',
        'access_finance_ibtf',
        'access_finance_arf',
    ];
}
