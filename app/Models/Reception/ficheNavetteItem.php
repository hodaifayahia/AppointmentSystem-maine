<?php

namespace App\Models\Reception;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\CONFIGURATION\Prestation;
use App\Models\Doctor;
use App\Models\B2B\Convention;
use App\Models\Patient;

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
        'convention_id',
        'uploaded_file',
        'family_authorization',
        'patient_id',
        'insured_id', // This will store the selected patient for convention
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'patient_share' => 'decimal:2',
        'doctor_share' => 'decimal:2',
        'user_remise_share' => 'decimal:2',
        'prise_en_charge_date' => 'datetime',
        'uploaded_file' => 'array',
        'family_authorization' => 'array',
    ];

    public function ficheNavette()
    {
        return $this->belongsTo(ficheNavette::class, 'fiche_navette_id');
    }

    /**
     * Get the doctor
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    /**
     * Get the prestation
     */
    public function prestation()
    {
        return $this->belongsTo(Prestation::class, 'prestation_id');
    }

    /**
     * Get the convention
     */
    public function convention()
    {
        return $this->belongsTo(Convention::class, 'convention_id');
    }

    /**
     * Get the patient
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * Get the insured patient
     */
    public function insuredPatient()
    {
        return $this->belongsTo(Patient::class, 'insured_id');
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

    /**
     * Get uploaded files as array
     */
    public function getUploadedFilesAttribute($value)
    {
        return $value ? (is_string($value) ? json_decode($value, true) : $value) : [];
    }

    /**
     * Get family authorizations as array
     */
    public function getFamilyAuthorizationsAttribute($value)
    {
        return $value ? (is_string($value) ? json_decode($value, true) : $value) : [];
    }

    /**
     * Scope to get items grouped by insured patient
     */
    public function scopeGroupedByInsured($query)
    {
        return $query->with(['insuredPatient', 'convention', 'prestation'])
                    ->orderBy('insured_id')
                    ->orderBy('convention_id')
                    ->orderBy('created_at');
    }

    /**
     * Get convention prescription data for this item
     */
    public function getConventionPrescriptionData()
    {
        return [
            'convention_id' => $this->convention_id,
            'prise_en_charge_date' => $this->prise_en_charge_date,
            'family_authorization' => $this->family_authorization,
            'uploaded_files' => $this->uploaded_file,
            'insured_patient' => $this->insuredPatient,
            'prestations' => [$this->prestation],
        ];
    }
}
