<?php
// filepath: d:\Projects\AppointmentSystem\AppointmentSystem-main\app\Services\Reception\ReceptionService.php

namespace App\Services\Reception;

use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use App\Models\CONFIGURATION\Prestation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Class ReceptionService
 * @package App\Services\Reception
 */
class ReceptionService
{
    /**
     * Create a new fiche navette
     */
    public function createFicheNavette(array $data): ficheNavette
    {
        DB::beginTransaction();

        try {
            $fiche = ficheNavette::create([
                'patient_id' => $data['patient_id'],
                'creator_id' => Auth::id(),
                'fiche_date' => $data['fiche_date'],
                'status' => $data['status'] ?? 'pending'
            ]);

            DB::commit();
            return $fiche->fresh(['items', 'patient', 'creator']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing fiche navette
     */
    public function updateFicheNavette(ficheNavette $ficheNavette, array $data): ficheNavette
    {
        DB::beginTransaction();

        try {
            $ficheNavette->update($data);

            DB::commit();
            return $ficheNavette->fresh(['items', 'patient', 'creator']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a fiche navette
     */
    public function deleteFicheNavette(ficheNavette $ficheNavette): bool
    {
        DB::beginTransaction();

        try {
            // Delete all associated items first
            $ficheNavette->items()->delete();
            
            // Delete the fiche navette
            $ficheNavette->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Change the status of a fiche navette
     */
    public function changeFicheNavetteStatus(ficheNavette $ficheNavette, string $newStatus): ficheNavette
    {
        $ficheNavette->update(['status' => $newStatus]);
        return $ficheNavette->fresh(['items', 'patient', 'creator']);
    }

    /**
     * Add a prestation to an existing fiche navette with dependencies.
     */
    public function addPrestationToFiche(ficheNavette $ficheNavette, array $prestationData): ficheNavetteItem
    {
        DB::beginTransaction();

        try {
            // Get the prestation to check for dependencies
            $prestation = Prestation::findOrFail($prestationData['prestation_id']);
            
            // Create the main prestation item
            $mainItem = ficheNavetteItem::create([
                'fiche_navette_id' => $ficheNavette->id,
                'prestation_id' => $prestationData['prestation_id'],
                'appointment_id' => $prestationData['appointment_id'] ?? null,
                'status' => $prestationData['status'] ?? 'pending',
                'base_price' => $prestationData['base_price'] ?? $prestation->public_price,
                'final_price' => $prestationData['final_price'] ?? $prestationData['base_price'] ?? $prestation->public_price,
                'patient_share' => $prestationData['patient_share'] ?? $prestationData['base_price'] ?? $prestation->public_price,
                'doctor_share' => $prestationData['doctor_share'] ?? 0,
                'doctor_id' => $prestationData['doctor_id'] ?? null,
                'modality_id' => $prestationData['modality_id'] ?? null,
                'user_remise_id' => $prestationData['user_remise_id'] ?? null,
                'user_remise_share' => $prestationData['user_remise_share'] ?? 0,
                'prise_en_charge_date' => now(),
            ]);

            // Handle dependencies from required_prestations_info or explicitly provided ones
            if (isset($prestationData['dependency_prestation_ids']) && is_array($prestationData['dependency_prestation_ids'])) {
                $this->addRequiredDependencies($ficheNavette, $prestationData['dependency_prestation_ids']);
            } elseif ($prestation->required_prestations_info && is_array($prestation->required_prestations_info)) {
                // Auto-add required dependencies from prestation's required_prestations_info
                $requiredIds = array_filter($prestation->required_prestations_info, function($id) {
                    return $id !== "!" && is_numeric($id);
                });
                
                if (!empty($requiredIds)) {
                    $this->addRequiredDependencies($ficheNavette, $requiredIds);
                }
            }

            DB::commit();
            return $mainItem->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Add selected dependencies as required prestations
     */
    private function addRequiredDependencies(ficheNavette $ficheNavette, array $dependencyPrestationIds): void
    {
        foreach ($dependencyPrestationIds as $prestationId) {
            // Convert to integer if it's a string
            $prestationId = is_string($prestationId) ? intval($prestationId) : $prestationId;
            
            // Skip if not a valid number
            if (!is_numeric($prestationId) || $prestationId <= 0) {
                continue;
            }

            // Check if this prestation is already added to avoid duplicates
            $existingItem = ficheNavetteItem::where('fiche_navette_id', $ficheNavette->id)
                ->where('prestation_id', $prestationId)
                ->first();

            if (!$existingItem) {
                $dependencyPrestation = Prestation::find($prestationId);
                
                if ($dependencyPrestation) {
                    ficheNavetteItem::create([
                        'fiche_navette_id' => $ficheNavette->id,
                        'prestation_id' => $prestationId,
                        'status' => 'required', // Mark as required dependency
                        'base_price' => $dependencyPrestation->public_price,
                        'final_price' => $dependencyPrestation->public_price,
                        'patient_share' => $dependencyPrestation->public_price,
                        'doctor_share' => 0,
                        'prise_en_charge_date' => now(),
                    ]);
                }
            }
        }
    }
}
