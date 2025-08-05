<?php

namespace App\Http\Resources\Reception;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ficheNavetteResource extends JsonResource
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
            'patient_id' => $this->patient->id ?? null,
            'patient_name' => $this->patient->Firstname . ' ' . $this->patient->Lastname ?? null,
            'creator_id' => $this->creator_id,
            'creator_name' => $this->creator->name ?? null,
            'fiche_date' => $this->fiche_date,
            'status' => $this->status,
            'items' => ficheNavetteItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->items()->count(),
            'total_amount' => $this->items->sum('final_price'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
