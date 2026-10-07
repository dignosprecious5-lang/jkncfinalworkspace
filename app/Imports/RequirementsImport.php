<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use App\Models\Service;
use App\Models\ServiceVersion;
use App\Models\ServiceActivity;

class ServicesMultiImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Services' => new ServicesSheetImport(),
            'Main Activities' => new MainActivitiesSheetImport(),
            'Sub-Activities' => new SubActivitiesSheetImport(),
        ];
    }
}

// 1. SERVICES SHEET IMPORT (Section 16.1 & 16.2)
class ServicesSheetImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $header = $rows->shift();

        foreach ($rows as $row) {
            if (empty($row[0])) continue;

            $lastService = Service::latest('id')->first();
            $nextId = $lastService ? $lastService->id + 1 : 1;
            $serviceCode = 'SVC-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $service = Service::create([
                'service_code'        => $serviceCode,
                'name'                => $row[0],
                'category'            => $row[1] ?? 'General',
                'engagement_behavior' => $row[2] ?? 'regular',
                'status'              => 'draft',
            ]);

            $version = ServiceVersion::create([
                'service_id'     => $service->id,
                'version_number' => 'V1.0',
                'status'         => 'draft',
                'is_active'      => true,
                'standard_price' => $row[3] ?? 0,
            ]);

            $service->update(['active_version_id' => $version->id]);
        }
    }
}

// 2. MAIN ACTIVITIES SHEET IMPORT
class MainActivitiesSheetImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $header = $rows->shift();

        foreach ($rows as $row) {
            if (empty($row[0]) || empty($row[1])) continue; 

            $service = Service::where('service_code', $row[0])->first();
            if (!$service || !$service->activeVersion) continue;

            ServiceActivity::create([
                'service_version_id'     => $service->activeVersion->id,
                'parent_id'              => null,
                'name'                   => $row[1],
                'sequence'               => $row[2] ?? 1,
                'expected_days'          => $row[3] ?? 1,
                'expected_working_hours' => $row[4] ?? 8,
                'is_mandatory'           => true,
                'is_billable'            => true,
            ]);
        }
    }
}

// 3. SUB-ACTIVITIES SHEET IMPORT
class SubActivitiesSheetImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $header = $rows->shift();

        foreach ($rows as $row) {
            if (empty($row[0]) || empty($row[1]) || empty($row[2])) continue;

            $service = Service::where('service_code', $row[0])->first();
            if (!$service || !$service->activeVersion) continue;

            $mainActivity = ServiceActivity::where('service_version_id', $service->activeVersion->id)
                ->where('name', $row[1])
                ->whereNull('parent_id')
                ->first();

            if (!$mainActivity) continue;

            ServiceActivity::create([
                'service_version_id'     => $service->activeVersion->id,
                'parent_id'              => $mainActivity->id,
                'name'                   => $row[2],
                'expected_days'          => $row[3] ?? 1,
                'expected_working_hours' => $row[4] ?? 4,
                'is_mandatory'           => true,
                'is_billable'            => true,
            ]);
        }
    }
}