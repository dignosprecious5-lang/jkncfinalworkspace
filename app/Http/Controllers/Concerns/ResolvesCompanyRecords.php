<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Company;
use App\Models\GisRecord;
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
                $gis = $this->latestApprovedCompanyGis($company);

                return [
                    'id' => $record->id,
                    'company_name' => $gis?->corporation_name ?: $record->company_name,
                    'company_type' => $bif?->business_organization ? str_replace('_', ' ', $bif->business_organization) : null,
                    'bif_no' => $bif?->bif_no,
                    'gis_id' => $gis?->id,
                    'email' => $gis?->email ?: $record->email,
                    'phone' => $gis?->official_mobile ?: $bif?->business_phone ?: $record->phone,
                    'website' => $gis?->website ?: $record->website,
                    'description' => $record->description,
                    'address' => $gis?->principal_address ?: $gis?->business_address ?: $record->address,
                    'mobile_no' => $bif?->mobile_no,
                    'tin_no' => $gis?->tin ?: $bif?->tin_no,
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

    protected function latestApprovedCompanyGis(int $company): ?GisRecord
    {
        if (! Schema::hasTable('gis_records') || ! Schema::hasColumn('gis_records', 'company_id')) {
            return null;
        }

        return GisRecord::query()
            ->where('company_id', $company)
            ->when(
                Schema::hasColumn('gis_records', 'approval_status') || Schema::hasColumn('gis_records', 'workflow_status'),
                function ($query) {
                    $query->where(function ($nested) {
                        if (Schema::hasColumn('gis_records', 'approval_status')) {
                            $nested->where('approval_status', 'Approved');
                        }

                        if (Schema::hasColumn('gis_records', 'workflow_status')) {
                            $nested->orWhere('workflow_status', 'Accepted');
                        }
                    });
                }
            )
            ->latest('id')
            ->first();
    }
}
