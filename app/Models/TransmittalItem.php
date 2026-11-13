<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransmittalItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transmittal_id',
        'item_no',
        'particular',
        'unique_id',
        'qty',
        'description',
        'remarks',
        'attachment_path',
        // V2 fields stay independent; do not coerce or synchronize V1 qty/indexes.
        'document_id',
        'position',
        'quantity',
    ];

    public function transmittal()
    {
        return $this->belongsTo(Transmittal::class);
    }

    public function document()
    {
        return $this->belongsTo(TransmittalDocument::class);
    }
}
