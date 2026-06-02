<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransmittalAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'transmittal_id',
        'file_path',
        'original_name',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    public function transmittal()
    {
        return $this->belongsTo(Transmittal::class);
    }
}
