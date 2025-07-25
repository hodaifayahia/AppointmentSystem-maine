<?php

namespace App\Models;

use App\Models\Doctor;
use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Specialization extends Model
{
    use SoftDeletes;



    protected $fillable = [
        'id',
        'name',
        'photo',
        'description',
        'service_id',
        'is_active'
    ];

    public function doctors()
    {
        return $this->belongsToMany(Doctor::class);
    }
 public function service() {
   return $this->belongsTo(Service::class, 'service_id', 'id');
}
}
