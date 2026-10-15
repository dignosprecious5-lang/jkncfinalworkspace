<?php

namespace App\Exports;

use App\Models\Requirement;
use App\Models\ClientRequirement;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RequirementsExport implements FromCollection, WithHeadings
{
    /**
     * @return Enumerable
     */
    public function collection(): Enumerable
    {
        // Gagamit ng ClientRequirement o Requirement depende sa totoong model name
        $model = class_exists(Requirement::class) ? Requirement::class : ClientRequirement::class;

        return $model::select('id', 'document_name', 'client_type', 'source', 'is_mandatory', 'status', 'created_at')->get();
    }

    public function headings(): array
    {
        return ['Requirement ID', 'Document Name', 'Client Type', 'Source', 'Is Mandatory', 'Status', 'Created At'];
    }
}