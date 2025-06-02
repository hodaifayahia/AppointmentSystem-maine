<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class prescriptionMedication extends Model
{
    protected $fillable = [
        'prescription_id',
        'cd_active_substance',
        'brand_name',
        'pharmaceutical_form',
        'dose_per_intake',
        'num_intakes_per_day',
        'duration_or_boxes'
    ];
    
    /**
     * Get the prescription that owns the medication.
     */
    public function prescription()
    {
        return $this->belongsTo(prescription::class);
    }
}