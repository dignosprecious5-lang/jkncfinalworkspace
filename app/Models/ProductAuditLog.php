<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAuditLog extends Model
{
    protected $table = 'product_audit_logs';

    protected $fillable = [
        'product_id',
        'user_name',
        'action',
        'title',
        'details',
        'description',
        'message',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}