<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stockholder extends Model
{
    protected $fillable = ['gis_id','stockholder_name','email','address','gender','nationality','incr','share_type','shares','amount','ownership_percentage','amount_paid','tin'];
}
