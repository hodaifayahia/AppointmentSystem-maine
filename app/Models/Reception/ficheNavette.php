<?php

namespace App\Models\Reception;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Patient;
use App\Models\Reception\ficheNavetteItem;

class ficheNavette extends Model
{
    
    protected $table = 'fiche_navette';
    protected $fillable = [
        'id',
        'patient_id',
        'creator_id',
        'fiche_date',
        'status'
    ];

    protected $casts = [
        'arrival_time' => 'datetime',
        'departure_time' => 'datetime'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function items()
    {
        return $this->hasMany(ficheNavetteItem::class, 'fiche_navette_id');
    }
}
