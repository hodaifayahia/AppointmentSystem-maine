<?php

namespace App\Models\CONFIGURATION; // Corrected namespace from CONFIGURATIONC to CONFIGURATION

use Illuminate\Database\Eloquent\Model;
use App\Models\CONFIGURATION\ModalityType; // Ensure this is correctly imported
use App\Models\INFRASTRUCTURE\Room; // Ensure this is correctly imported
use App\Models\CONFIGURATION\Service; // NEW: Import the Service model

class Modality extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'modalities'; // Assuming your table name is 'modalities'

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'internal_code',
        'modality_type_id',
        'dicom_ae_title',
        'port',
        'physical_location_id',
        'operational_status',
        // --- New Fields Added ---
        'service_id',
        'integration_protocol',
        'connection_configuration',
        'data_retrieval_method',
        'ip_address',
        // --- End New Fields ---
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // If 'operational_status' is stored as a string ('active', 'inactive')
        // and you want to cast it to a boolean when retrieved, you can do:
        // 'operational_status' => 'boolean',
        // However, your controller already handles this conversion, so it might not be strictly necessary here
        // unless you want it to be a boolean directly from the model instance.
    ];

    /**
     * Get the modality type that owns the Modality.
     */
    public function modalityType()
    {
        return $this->belongsTo(ModalityType::class);
    }

    /**
     * Get the physical location that owns the Modality.
     */
    public function physicalLocation()
    {
        return $this->belongsTo(Room::class, 'physical_location_id');
    }

    /**
     * NEW: Get the service/department that owns the Modality.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}