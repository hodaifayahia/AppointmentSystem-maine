<?php

namespace App\Http\Resources\Reception;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ficheNavetteItemResource extends JsonResource
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
            'fiche_navette_id' => $this->fiche_navette_id,
            'prestation_id' => $this->prestation_id,
            'prestation' => [
                'id' => $this->prestation->id ?? null,
                'name' => $this->prestation->name ?? null,
                'code' => $this->prestation->code ?? null,
                'public_price' => $this->prestation->public_price ?? null,
            ],
            'appointment_id' => $this->appointment_id,
            'status' => $this->status,
            'base_price' => $this->base_price,
            'user_remise_id' => $this->user_remise_id,
            'user_remise_share' => $this->user_remise_share,
            'doctor_share' => $this->doctor_share,
            'doctor_id' => $this->doctor_id,
            'final_price' => $this->final_price,
            'patient_share' => $this->patient_share,
            'modality_id' => $this->modality_id,
            'prise_en_charge_date' => $this->prise_en_charge_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
