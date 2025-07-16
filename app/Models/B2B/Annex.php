<?php

namespace App\Models\B2B;

use Illuminate\Database\Eloquent\Model;

class Annex extends Model
{
    protected $fillable =[
        'annex_name',
        'convention_id',
        'service_id',
        'description',
        'is_active',
        'created_by',
        'updated_by'
    ];
}
