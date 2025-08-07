<?php

namespace App\Models\Reception;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\CONFIGURATION\Prestation;
use App\Models\Doctor;

class ficheNavetteItem extends Model
{
    protected $table = 'fiche_navette_items';

    protected $fillable = [
        'fiche_navette_id',
        'prestation_id',
        'package_id',
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
        'custom_name',
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
    //doctor relation 
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
    public function prestation()
    {
        return $this->belongsTo(Prestation::class, 'prestation_id');
    }

    /**
     * Get dependencies from ItemDependency table
     */
    public function dependencies(): HasMany
    {
        return $this->hasMany(ItemDependency::class, 'parent_item_id');
    }

    /**
     * Get dependencies with their prestations loaded
     */
    public function dependenciesWithPrestations()
    {
        return $this->dependencies()->with('dependencyPrestation');
    }

    /**
     * Check if this item has dependencies
     */
    public function hasDependencies(): bool
    {
        return $this->dependencies()->count() > 0;
    }
}
