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
            'package_id' => $this->package_id,
            'appointment_id' => $this->appointment_id,
            'status' => $this->status,
            'base_price' => $this->base_price,
            'final_price' => $this->final_price,
            'patient_share' => $this->patient_share,
            'doctor_share' => $this->doctor_share,
            'user_remise_share' => $this->user_remise_share,
            'doctor_id' => $this->doctor_id,
            'modality_id' => $this->modality_id,
            'prise_en_charge_date' => $this->prise_en_charge_date,
            'custom_name' => $this->custom_name,
            'convention_id' => $this->convention_id,
            'insured_id' => $this->insured_id,
            'family_authorization' => $this->family_authorization,
            'uploaded_file' => $this->uploaded_file,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'prestation' => $this->whenLoaded('prestation', function () {
                return [
                    'id' => $this->prestation->id,
                    'name' => $this->prestation->name,
                    'internal_code' => $this->prestation->internal_code,
                    'description' => $this->prestation->description,
                    'public_price' => $this->prestation->public_price,
                    'service_id' => $this->prestation->service_id,
                    'specialization_id' => $this->prestation->specialization_id,
                    'service' => isset($this->prestation->service) ? [
                        'id' => $this->prestation->service->id,
                        'name' => $this->prestation->service->name,
                    ] : null,
                    'specialization' => isset($this->prestation->specialization) ? [
                        'id' => $this->prestation->specialization->id,
                        'name' => $this->prestation->specialization->name,
                    ] : null,
                ];
            }),
            
            'doctor' => $this->whenLoaded('doctor', function () {
                return [
                    'id' => $this->doctor->id,
                    'name' => $this->doctor->name,
                    'email' => $this->doctor->email,
                    'specialization_id' => $this->doctor->specialization_id ?? null,
                ];
            }),
            
            'convention' => $this->whenLoaded('convention', function () {
                return [
                    'id' => $this->convention->id,
                    'contract_name' => $this->convention->contract_name,
                    'company_name' => $this->convention->company_name,
                    'status' => $this->convention->status,
                    'is_active' => $this->convention->is_active,
                ];
            }),
            
            'insuredPatient' => $this->whenLoaded('insuredPatient', function () {
                return [
                    'id' => $this->insuredPatient->id,
                    'first_name' => $this->insuredPatient->first_name,
                    'last_name' => $this->insuredPatient->last_name,
                    'phone' => $this->insuredPatient->phone,
                    'email' => $this->insuredPatient->email,
                ];
            }),
            
            // Fixed dependencies - Use dependencyPrestation, not parentItem->prestation
            'dependencies' => $this->whenLoaded('dependencies', function () {
                return $this->dependencies->map(function ($dependency) {
                    $prestationInfo = null;
                    $prestationName = 'Unknown Dependency';
                    

                    // Get the dependency prestation (the prestation that this dependency refers to)
                    if ($dependency->relationLoaded('dependencyPrestation') && $dependency->dependencyPrestation) {
                        $prestation = $dependency->dependencyPrestation;
                        
                        $prestationInfo = [
                            'id' => $prestation->id,
                            'name' => $prestation->name ?? 'Unknown',
                            'internal_code' => $prestation->internal_code ?? 'Unknown',
                            'description' => $prestation->description,
                            'public_price' => $prestation->public_price,
                            'service_id' => $prestation->service_id ?? null,
                            'specialization_id' => $prestation->specialization_id ?? null,
                            'specialization' => ($prestation->relationLoaded('specialization') && $prestation->specialization) ? [
                                'id' => $prestation->specialization->id,
                                'name' => $prestation->specialization->name,
                            ] : null,
                        ];
                        
                        $prestationName = $prestation->name;
                    } else {
                        // Fallback: try to load the prestation directly
                        if ($dependency->dependent_prestation_id) {
                            try {
                                $prestation = \App\Models\CONFIGURATION\Prestation::find($dependency->dependent_prestation_id);
                                if ($prestation) {
                                    $prestationInfo = [
                                        'id' => $prestation->id,
                                        'name' => $prestation->name,
                                        'internal_code' => $prestation->internal_code,
                                        'description' => $prestation->description,
                                        'public_price' => $prestation->public_price,
                                        'service_id' => $prestation->service_id ?? null,
                                        'specialization_id' => $prestation->specialization_id ?? null,
                                        'specialization' => null,
                                    ];
                                    $prestationName = $prestation->name;
                                }
                            } catch (\Exception $e) {
                                \Log::warning('Could not load prestation for dependency', [
                                    'dependency_id' => $dependency->id,
                                    'dependent_prestation_id' => $dependency->dependent_prestation_id,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    }

                    // Display name priority: custom_name > prestation name > fallback
                    $displayName = $prestationName;
                    if (!empty($dependency->custom_name) && trim($dependency->custom_name) !== '') {
                        $displayName = $dependency->custom_name;
                    }

                    return [
                        'id' => $dependency->id,
                        'parent_item_id' => $dependency->parent_item_id,
                        'dependent_prestation_id' => $dependency->dependent_prestation_id,
                        'dependency_type' => $dependency->dependency_type ?? 'prestation',
                        'doctor_id' => $dependency->doctor_id,
                        'base_price' => $dependency->base_price,
                        'final_price' => $dependency->final_price,
                        'status' => $dependency->status,
                        'notes' => $dependency->notes,
                        'custom_name' => $dependency->custom_name,
                        'display_name' => $displayName,
                        'created_at' => $dependency->created_at,
                        'updated_at' => $dependency->updated_at,
                        'dependencyPrestation' => $prestationInfo,
                    ];
                });
            }),
        ];
    }
}
