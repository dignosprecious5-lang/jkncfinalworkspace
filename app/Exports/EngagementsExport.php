<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EngagementsExport implements FromCollection, WithHeadings
{
    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        return DB::table('engagements')
            ->select('id', 'title', 'client_name', 'type', 'recurrence_rule', 'status', 'created_at')
            ->get();
    }

    public function headings(): array
    {
        return ['Engagement ID', 'Title', 'Client Name', 'Type', 'Recurrence', 'Status', 'Created At'];
    }
}