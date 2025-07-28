<?php

namespace App\Models\CONFIGURATION;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Specialization;


class Prestation extends Model
{
    use HasFactory;

      protected $fillable = [
        'name',
        'internal_code',
        'billing_code',
        'description',
        'service_id',

        'specialization_id',
        'type', // <-- Missing in your provided fillable
        'public_price',
        'convenience_prix',
        'vat_rate',
        'night_tariff', // Corrected from 'Tarif_de_nuit' in your validation
        'consumables_cost',
        'is_social_security_reimbursable',
        'reimbursement_conditions', // <-- Missing
        'non_applicable_discount_rules', // <-- Missing
        'fee_distribution_model', // <-- Missing
        'primary_doctor_share', // <-- Missing
        'primary_doctor_is_percentage', // <-- Missing
        'assistant_doctor_share', // <-- Missing
        'assistant_doctor_is_percentage', // <-- Missing
        'technician_share', // <-- Missing
        'technician_is_percentage', // <-- Missing
        'clinic_share', // <-- Missing
        'clinic_is_percentage', // <-- Missing
        'default_payment_type',
        'min_versement_amount',
        'requires_hospitalization',
        'default_hosp_nights',
        'required_modality_type_id',
        'default_duration_minutes',
        'required_prestations_info', // <-- Missing
        'patient_instructions', // <-- Missing
        'required_consents', // <-- Missing
        'is_active',
        // 'Tarif_de_nuit', // This seems to be replaced by 'night_tariff' in validation
        // 'Tarif_de_nuit_is_active' // This field is not present in your PrestationRequest validation rules.
                                  // If it's still needed, add validation for it.
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_social_security_reimbursable' => 'boolean',
        'requires_hospitalization' => 'boolean',
        'primary_doctor_is_percentage' => 'boolean',
        'assistant_doctor_is_percentage' => 'boolean',
        'technician_is_percentage' => 'boolean',
        'clinic_is_percentage' => 'boolean',
        'non_applicable_discount_rules' => 'array', // Assuming this is stored as JSON
        'required_prestations_info' => 'array', // Assuming this is stored as JSON
        'required_consents' => 'array', // Assuming this is stored as JSON
        'public_price' => 'decimal:2', // Or appropriate precision
        'vat_rate' => 'decimal:2',
        'night_tariff' => 'decimal:2',
        'consumables_cost' => 'decimal:2',
        'min_versement_amount' => 'decimal:2',
        // For shares, if they contain '%' or are not strictly numbers in the DB,
        // you might keep them as 'string' but ensure they are numeric before saving.
        // If they are purely numeric in DB, you can cast to 'decimal'.
        'primary_doctor_share' => 'string', // Or 'decimal:2' if only numbers
        'assistant_doctor_share' => 'string',
        'technician_share' => 'string',
        'clinic_share' => 'string',
    ];

    // Relationships
      public function service(): BelongsTo 
    {
        return $this->belongsTo(Service::class);
    }
    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class , 'specialization_id');
    }

    public function modalityType(): BelongsTo
    {
        return $this->belongsTo(ModalityType::class, 'required_modality_type_id');
    }
     public function prestationPrices(): HasMany // Assuming PrestationPrice is PrestationPricing
    {
        return $this->hasMany(PrestationPricing::class, 'prestation_id'); // Corrected foreign key
    }

    // Accessors
    public function getPriceWithVatAttribute()
    {
        return $this->public_price * (1 + $this->vat_rate / 100);
    }

    public function getFormattedDurationAttribute()
    {
        if (!$this->default_duration_minutes) return null;

        $hours = floor($this->default_duration_minutes / 60);
        $minutes = $this->default_duration_minutes % 60;

        return $hours > 0 ? "{$hours}h {$minutes}min" : "{$minutes}min";
    }

    // Add an accessor for formatted_id if it's not a direct column
    // For example, if you want 'internal_code' or 'billing_code' as the "code"
    public function getFormattedIdAttribute(): string
    {
        return $this->internal_code ?: $this->billing_code; // Or whatever logic you use
    }
}
