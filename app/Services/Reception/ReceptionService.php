<?php
// filepath: d:\Projects\AppointmentSystem\AppointmentSystem-main\app\Services\Reception\ReceptionService.php

namespace App\Services\Reception;

use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use App\Models\Reception\ItemDependency;
use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\PrestationPackage;
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
     * Create a new fiche navette with items and dependencies
     */
    public function createFicheNavetteWithItems(array $data): ficheNavette
    {
        DB::beginTransaction();

        try {
            // 1. Create the main Fiche Navette entry
            $fiche = ficheNavette::create([
                'patient_id' => $data['patient_id'],
                'creator_id' => Auth::id(),
                'reference' => $this->generateReference(),
                'status' => 'pending',
                'fiche_date' => now(),
                'total_amount' => 0,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id()
            ]);

            $totalAmount = 0;

            // 2. Handle different types of prestations
            if (isset($data['type']) && $data['type'] === 'prestation') {
                if (isset($data['prestations']) && !empty($data['prestations'])) {
                    // Add main prestation ONLY to fiche_navette_items
                    $mainPrestation = $data['prestations'][0];
                    $mainItem = $this->addPrestationToFiche($fiche, $mainPrestation);
                    $totalAmount += $mainItem->final_price;

                    // Add dependencies ONLY to item_dependencies table
                    if (isset($data['dependencies']) && !empty($data['dependencies'])) {
                        $this->storeDependenciesOnly($mainItem, $data['dependencies']);
                        // Don't add dependency prices to total - only main prestation price
                    }
                } elseif (isset($data['packages']) && !empty($data['packages'])) {
                    // Add package
                    $packageData = $data['packages'][0];
                    $packageItems = $this->addPackageToFiche($fiche, $packageData);
                    foreach ($packageItems as $item) {
                        $totalAmount += $item->final_price;
                    }
                }
            } 
            // 3. Handle custom prestations (first one is main, rest are dependencies)
            elseif (isset($data['type']) && $data['type'] === 'custom' && isset($data['customPrestations'])) {
                $customItems = $this->addCustomPrestationsToFiche($fiche, $data['customPrestations']);
                // Only add the first (main) prestation price to total
                if (!empty($customItems)) {
                    $totalAmount += $customItems[0]->final_price;
                }
            }

            // 4. Update the total amount
            $fiche->update(['total_amount' => $totalAmount]);

            DB::commit();
            return $fiche->fresh(['items.prestation', 'items.dependencies.dependentItem.prestation', 'patient', 'creator']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Add items to an existing fiche navette
     */
    public function addItemsToFicheNavette(ficheNavette $ficheNavette, array $data): ficheNavette
    {
        DB::beginTransaction();

        try {
            $totalAmount = $ficheNavette->total_amount ?? 0;

            // Handle different types of prestations
            if (isset($data['type']) && $data['type'] === 'prestation') {
                if (isset($data['prestations']) && !empty($data['prestations'])) {
                    // Add main prestation ONLY to fiche_navette_items
                    $mainPrestation = $data['prestations'][0];
                    $mainItem = $this->addPrestationToFiche($ficheNavette, $mainPrestation);
                    $totalAmount += $mainItem->final_price;

                    // Add dependencies ONLY to item_dependencies table
                    if (isset($data['dependencies']) && !empty($data['dependencies'])) {
                        $this->storeDependenciesOnly($mainItem, $data['dependencies']);
                    }
                } elseif (isset($data['packages']) && !empty($data['packages'])) {
                    // Add package
                    $packageData = $data['packages'][0];
                    $packageItems = $this->addPackageToFiche($ficheNavette, $packageData);
                    foreach ($packageItems as $item) {
                        $totalAmount += $item->final_price;
                    }
                }
            } 
            // Handle custom prestations (first one is main, rest are dependencies)
            elseif (isset($data['type']) && $data['type'] === 'custom' && isset($data['customPrestations'])) {
                $customItems = $this->addCustomPrestationsToFiche($ficheNavette, $data['customPrestations']);
                // Only add the first (main) prestation price to total
                if (!empty($customItems)) {
                    $totalAmount += $customItems[0]->final_price;
                }
            }

            // Update the total amount
            $ficheNavette->update(['total_amount' => $totalAmount]);

            DB::commit();
            return $ficheNavette->fresh([
                'items.prestation', 
                'items.dependencies.dependencyPrestation', 
                'patient', 
                'creator'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Add a single prestation to a fiche navette (MAIN prestation only)
     */
    public function addPrestationToFiche(ficheNavette $ficheNavette, array $prestationData , $selectedDoctorId = null): ficheNavetteItem
    {
        $prestation = Prestation::findOrFail($prestationData['id']);
            // dd($prestationData);
        return ficheNavetteItem::create([
            'fiche_navette_id' => $ficheNavette->id,
            'prestation_id' => $prestation->id,
            'doctor_id' => $selectedDoctorId ?? null,
            'status' => 'pending',
            'base_price' => $prestation->public_price,
            'final_price' => $prestation->public_price,
            'patient_share' => $prestation->public_price,
            'prise_en_charge_date' => now(),
        ]);
    }

    /**
     * Store dependencies ONLY in ItemDependency table (NOT in fiche_navette_items)
     */
    private function storeDependenciesOnly(ficheNavetteItem $parentItem, array $dependenciesData): void
    {
        // dd($dependenciesData);
        foreach ($dependenciesData as $dependencyData) {
            // Create dependency record in ItemDependency table only
            ItemDependency::create([
                'parent_item_id' => $parentItem->id,
                'dependency_type' => ItemDependency::TYPE_REQUIRED ?? null,
                'dependency_prestation_id' => $dependencyData['id'], // Store prestation ID directly
                'notes' => 'Required dependency for ' . ($parentItem->prestation->name ?? 'prestation')
            ]);
        }
    }

    /**
     * Add custom prestations - first one is main, rest are dependencies
     */
    public function addCustomPrestationsToFiche(ficheNavette $ficheNavette, array $customPrestationsData): array
    {
        $addedItems = [];
        $mainItem = null;
        //  dd($customPrestationsData);
        foreach ($customPrestationsData as $index => $prestationData) {
            
            $prestation = Prestation::findOrFail($prestationData['id']);

            if ($index === 0) {
                // First item is the MAIN prestation - store in fiche_navette_items
                $mainItem = ficheNavetteItem::create([
                    'fiche_navette_id' => $ficheNavette->id,
                    'prestation_id' => $prestation->id,
                    'doctor_id' => $prestationData['selected_doctor_id'] ?? $prestationData['doctor_id'] ?? null,
                    'custom_name' => $prestationData['display_name'] ?? null,
                    'status' => 'pending',
                    'base_price' => $prestation->public_price,
                    'final_price' => $prestation->public_price,
                    'patient_share' => $prestation->public_price,
                    'prise_en_charge_date' => now(),
                ]);

                $addedItems[] = $mainItem;
            } else {
                // Subsequent items are dependencies - store ONLY in item_dependencies
                if ($mainItem) {
                    ItemDependency::create([
                        'parent_item_id' => $mainItem->id,
                        'dependent_item_id' => null, // No separate item created
                        // 'dependency_type' => ItemDependency::TYPE_CUSTOM_GROUP,
                        'dependency_prestation_id' => $prestation->id, // Store prestation ID directly
                        'notes' => 'Part of custom prestation group: ' . ($prestationData['display_name'] ?? 'Custom Group')
                    ]);
                }
            }
        }

        return $addedItems;
    }

    /**
     * Add package - first item is main, rest are dependencies
     */
    public function addPackageToFiche(ficheNavette $ficheNavette, array $packageData): array
    {
        $package = PrestationPackage::with('items.prestation')->findOrFail($packageData['id']);
        $addedItems = [];
        $mainItem = null;

        foreach ($package->items as $index => $packageItem) {
            if ($index === 0) {
                // First item is the MAIN prestation - store in fiche_navette_items
                $mainItem = ficheNavetteItem::create([
                    'fiche_navette_id' => $ficheNavette->id,
                    'prestation_id' => $packageItem->prestation->id,
                    'package_id' => $package->id,
                    'status' => 'pending',
                    'base_price' => $packageItem->prestation->public_price,
                    'final_price' => $packageItem->prestation->public_price,
                    'patient_share' => $packageItem->prestation->public_price,
                    'doctor_id' => $packageData['selected_doctor_id'] ?? $packageData['doctor_id'] ?? null,
                    'prise_en_charge_date' => now(),
                ]);

                $addedItems[] = $mainItem;
            } else {
                // Subsequent package items are dependencies - store ONLY in item_dependencies
                if ($mainItem) {
                    ItemDependency::create([
                        'parent_item_id' => $mainItem->id,
                        'dependent_item_id' => null, // No separate item created
                        'dependency_type' => ItemDependency::TYPE_PACKAGE,
                        'dependency_prestation_id' => $packageItem->prestation->id, // Store prestation ID directly
                        'notes' => 'Package item from: ' . $package->name
                    ]);
                }
            }
        }

        return $addedItems;
    }

    /**
     * Update a specific fiche navette item
     */
    public function updateFicheNavetteItem(int $ficheNavetteId, int $itemId, array $data): ficheNavette
    {
        DB::beginTransaction();

        try {
            $ficheNavette = ficheNavette::findOrFail($ficheNavetteId);
            $item = ficheNavetteItem::where('fiche_navette_id', $ficheNavetteId)
                ->where('id', $itemId)
                ->firstOrFail();

            $item->update($data);

            DB::commit();
            return $ficheNavette->fresh(['items.prestation', 'items.dependencies', 'patient', 'creator']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove a fiche navette item and its dependencies
     */
    public function removeFicheNavetteItem(int $ficheNavetteId, int $itemId): ficheNavette
    {
        DB::beginTransaction();

        try {
            $ficheNavette = ficheNavette::findOrFail($ficheNavetteId);
            $item = ficheNavetteItem::where('fiche_navette_id', $ficheNavetteId)
                ->where('id', $itemId)
                ->firstOrFail();

            // Remove all dependency relationships for this item
            ItemDependency::where('parent_item_id', $itemId)->delete();

            // Store the price to subtract from total
            $priceToSubtract = $item->final_price;

            // Delete the main item
            $item->delete();

            // Update the total amount
            $newTotal = max(0, ($ficheNavette->total_amount ?? 0) - $priceToSubtract);
            $ficheNavette->update(['total_amount' => $newTotal]);

            DB::commit();
            return $ficheNavette->fresh(['items.prestation', 'items.dependencies', 'patient', 'creator']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate a unique reference number
     */
    private function generateReference(): string
    {
        $prefix = 'FN';
        $date = now()->format('Ymd');
        $sequence = FicheNavette::whereDate('created_at', today())->count() + 1;
        return $prefix . $date . sprintf('%04d', $sequence);
    }
}
