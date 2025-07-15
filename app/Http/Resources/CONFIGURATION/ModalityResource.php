<?php

namespace App\Http\Resources\CONFIGURATION;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModalityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'internal_code' => $this->internal_code,
            'modality_type_id' => $this->modality_type_id,
            'dicom_ae_title' => $this->dicom_ae_title,
            'port' => $this->port,
            'physical_location_id' => $this->physical_location_id,
            'operational_status' => $this->operational_status,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            // --- New Fields in Resource ---
            'service_id' => $this->service_id,
            'integration_protocol' => $this->integration_protocol,
            'connection_configuration' => $this->connection_configuration,
            'data_retrieval_method' => $this->data_retrieval_method,
            'ip_address' => $this->ip_address,
            // --- End New Fields ---

            // Include relationships if they are loaded
            'modality_type' => $this->whenLoaded('modalityType', function () {
                return [
                    'id' => $this->modalityType->id,
                    'name' => $this->modalityType->name,
                ];
            }),
            // 'physical_location' => $this->whenLoaded('physicalLocation', function () {
            //     return [
            //         'id' => $this->physicalLocation->id,
            //         'name' => $this->physicalLocation->name,
            //     ];
            // }),
            'service' => $this->whenLoaded('service', function () { // NEW: Include service relationship
                return [
                    'id' => $this->service->id,
                    'name' => $this->service->name,
                ];
            }),
        ];
    }
}