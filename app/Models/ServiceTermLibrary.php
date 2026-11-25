<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceTermLibrary extends Model
{
    use HasFactory;

    protected $table = 'service_term_library';

    protected $fillable = [
        'scope',
        'service_area_id',
        'category',
        'title',
        'content',
        'sort_order',
        'status',
    ];

    public function serviceArea()
    {
        return $this->belongsTo(ServiceArea::class);
    }
}