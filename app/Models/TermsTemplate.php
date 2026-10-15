<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TermsTemplateItem; // <-- ITO ANG KULANG KAYA NAG-ERROR

class TermsTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'status'];

    public function items()
    {
        return $this->hasMany(TermsTemplateItem::class, 'template_id');
    }
}