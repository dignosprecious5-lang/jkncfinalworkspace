<?php

namespace App\Imports;

use App\Models\Requirement;
use App\Models\ClientRequirement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RequirementsImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     * @return Model|array|null
     */
    public function model(array $row): Model|array|null
    {
        $modelClass = class_exists(Requirement::class) ? Requirement::class : ClientRequirement::class;
        $instance = new $modelClass();
        $table = $instance->getTable();

        $data = [];

        // Check each column dynamically before adding it to $data
        if (Schema::hasColumn($table, 'service_id')) {
            $data['service_id'] = $row['service_id'] ?? 1;
        }

        if (Schema::hasColumn($table, 'document_name')) {
            $data['document_name'] = $row['document_name'] ?? $row['name'] ?? 'Imported Requirement';
        } elseif (Schema::hasColumn($table, 'name')) {
            $data['name'] = $row['document_name'] ?? $row['name'] ?? 'Imported Requirement';
        }

        if (Schema::hasColumn($table, 'description')) {
            $data['description'] = $row['description'] ?? null;
        }

        if (Schema::hasColumn($table, 'client_type')) {
            $data['client_type'] = $row['client_type'] ?? 'All';
        }

        if (Schema::hasColumn($table, 'source')) {
            $data['source'] = $row['source'] ?? 'Client-supplied';
        }

        if (Schema::hasColumn($table, 'is_mandatory')) {
            $data['is_mandatory'] = isset($row['is_mandatory']) ? (bool)$row['is_mandatory'] : true;
        }

        if (Schema::hasColumn($table, 'file_required')) {
            $data['file_required'] = isset($row['file_required']) ? (bool)$row['file_required'] : true;
        }

        if (Schema::hasColumn($table, 'status')) {
            $data['status'] = $row['status'] ?? 'pending';
        }

        return new $modelClass($data);
    }
}