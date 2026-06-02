<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employees')) {
            return;
        }

        DB::table('employees')
            ->whereNull('employment_status')
            ->update(['employment_status' => 'Active']);

        DB::table('employees')
            ->orderBy('id')
            ->get(['id', 'digital_id_token', 'verification_reference', 'digital_id_issued_at'])
            ->each(function ($employee) {
                DB::table('employees')
                    ->where('id', $employee->id)
                    ->update([
                        'verification_enabled' => true,
                        'digital_id_token' => $employee->digital_id_token ?: (string) Str::uuid(),
                        'verification_reference' => $employee->verification_reference ?: 'EV-' . now()->format('Y') . '-' . strtoupper(Str::random(6)),
                        'digital_id_issued_at' => $employee->digital_id_issued_at ?: now()->toDateString(),
                    ]);
            });
    }

    public function down(): void
    {
        DB::table('employees')->update(['verification_enabled' => false]);
    }
};
