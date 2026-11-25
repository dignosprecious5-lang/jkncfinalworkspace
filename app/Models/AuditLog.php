<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'module',
        'description',
        'ip_address',
    ];

    public static function log(
        string $action,
        string $module,
        string $description,
        $recordId = null
    ): void {
        try {
            $user = auth()->user();

            static::create([
                'user_id'     => $user?->id,
                'user_name'   => $user?->name ?? 'System User',
                'action'      => $action,
                'module'      => $module,
                'description' => $description,
                'ip_address'  => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('AuditLog creation failed: ' . $e->getMessage());
        }
    }
}
