<?php

namespace App\Models\Reception;

use Illuminate\Database\Eloquent\Model;
use App\Models\CONFIGURATION\Prestation;

class ficheNavetteItem extends Model
{
    protected $table = 'fiche_navette_items';

    protected $fillable = [
        'fiche_navette_id',
        'prestation_id',
        'appointment_id',
        'status',
        'base_price',
        'user_remise_id',
        'user_remise_share',
        'doctor_share',
        'doctor_id',
        'final_price',
        'patient_share',
        'modality_id',
        'prise_en_charge_date',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'patient_share' => 'decimal:2',
        'doctor_share' => 'decimal:2',
        'user_remise_share' => 'decimal:2',
        'prise_en_charge_date' => 'datetime',
    ];

    public function ficheNavette()
    {
        return $this->belongsTo(ficheNavette::class, 'fiche_navette_id');
    }

    public function prestation()
    {
        return $this->belongsTo(Prestation::class, 'prestation_id');
    }
}
