<?php

namespace App\Models\CONFIGURATION;

use Illuminate\Database\Eloquent\Model;
use  App\Models\Specialization;

class Service extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image_url',
        'start_date',
        'end_date',
        'agmentation',
        'is_active'
    ];
     // Add this relationship
  // In App\Models\CONFIGURATION\Service.php
public function specializations()
{
    return $this->hasMany(Specialization::class);
}

}
