<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealFinanceDocument extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['size' => 'integer'];

    public function deal() { return $this->belongsTo(Deal::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
