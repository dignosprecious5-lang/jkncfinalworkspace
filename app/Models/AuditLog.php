<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public static function log(string $action, string $module, string $description): void
    {
        try {
            $user = auth()->user();
            
            static::create([
                'user_id'     => $user ? $user->id : null,
                'user_name'   => $user ? $user->name : 'System User',
                'action'      => $action,
                'module'      => $module,
                'description' => $description,
                'ip_address'  => request()->ip(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("AuditLog creation failed: " . $e->getMessage());
        }
    }
}