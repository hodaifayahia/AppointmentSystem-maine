<?php

namespace App\Http\Resources\Reception;

use Illuminate\Http\Resources\Json\JsonResource;

class ficheNavetteItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'fiche_navette_id' => $this->fiche_navette_id,
            'prestation_id' => $this->prestation_id,
            'package_id' => $this->package_id,
            'doctor_id' => $this->doctor_id,
            'custom_name' => $this->custom_name,
            'status' => $this->status,
            'base_price' => $this->base_price,
            'final_price' => $this->final_price,
            'patient_share' => $this->patient_share,
            'doctor_share' => $this->doctor_share,
            'user_remise_share' => $this->user_remise_share,
            'prise_en_charge_date' => $this->prise_en_charge_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Main prestation details
            'prestation' => $this->when($this->relationLoaded('prestation'), function () {
                return [
                    'id' => $this->prestation->id,
                    'name' => $this->prestation->name,
                    'internal_code' => $this->prestation->internal_code,
                    'public_price' => $this->prestation->public_price,
                    'specialization_name' => $this->prestation->specialization->name ?? null,
                ];
            }),
            
            // Dependencies (prestations stored in ItemDependency table)
            'dependencies' => $this->when($this->relationLoaded('dependencies'), function () {
                return $this->dependencies->map(function ($dependency) {
                    return [
                        'id' => $dependency->id,
                        'dependency_type' => $dependency->dependency_type,
                        'notes' => $dependency->notes,
                        'dependency_prestation' => $dependency->dependencyPrestation ? [
                            'id' => $dependency->dependencyPrestation->id,
                            'name' => $dependency->dependencyPrestation->name,
                            'internal_code' => $dependency->dependencyPrestation->internal_code,
                            'public_price' => $dependency->dependencyPrestation->public_price,
                            'specialization_name' => $dependency->dependencyPrestation->specialization->name ?? null,
                        ] : null,
                    ];
                });
            }),
            
            // // Doctor details
            // 'doctor' => $this->when($this->relationLoaded('doctor'), function () {
            //     return [
            //         'id' => $this->doctor->id,
            //         'name' => $this->doctor->name,
            //         'email' => $this->doctor->email,
            //     ];
            // }),
        ];
    }
}
