<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

trait ResolvesCompanyRecords
{
    protected function resolveCompanyRecord(Request $request, int $company, array $defaultCompanies = []): array
    {
        if (Schema::hasTable('companies')) {
            $record = Company::query()->with('latestBif')->find($company);

            if ($record) {
                $bif = $record->latestBif;

                return [
                    'id' => $record->id,
                    'company_name' => $record->company_name,
                    'company_type' => $bif?->business_organization ? str_replace('_', ' ', $bif->business_organization) : null,
                    'bif_no' => $bif?->bif_no,
                    'email' => $record->email,
                    'phone' => $bif?->business_phone ?: $record->phone,
                    'website' => $record->website,
                    'description' => $record->description,
                    'address' => $record->address,
                    'mobile_no' => $bif?->mobile_no,
                    'tin_no' => $bif?->tin_no,
                    'status' => $bif?->status,
                    'owner_name' => $record->owner_name,
                    'primary_contact_id' => $record->primary_contact_id,
                    'created_at' => optional($record->created_at)->toDateTimeString(),
                    'custom_fields' => [],
                ];
            }
        }

        abort(404);
    }
}
