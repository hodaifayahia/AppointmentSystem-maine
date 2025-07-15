<?php

namespace App\Http\Resources;

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
            'modality_type' => new ModalityTypeResource($this->whenLoaded('modalityType')), // If you have a ModalityTypeResource
            'dicom_ae_title' => $this->dicom_ae_title,
            'port' => $this->port,
            // 'physical_location' => new PhysicalLocationResource($this->whenLoaded('physicalLocation')), // If you have a PhysicalLocationResource
            'operational_status' => $this->operational_status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
