<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DirectorOfficer extends Model
{
    protected $table = 'directors_officers';

    protected $fillable = ['gis_id','officer_name','email','address','gender','nationality','incr','stockholder','board','officer_type','committee','tin'];
}
