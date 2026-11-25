<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermsTemplateItem extends Model
{
    use HasFactory;

    protected $fillable = ['template_id', 'title', 'content', 'scope', 'sort_order'];

    public function template()
    {
        return $this->belongsTo(TermsTemplate::class, 'template_id');
    }
}