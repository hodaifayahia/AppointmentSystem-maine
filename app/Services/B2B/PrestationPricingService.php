<?php

namespace App\Services\B2B;

use App\Models\B2B\PrestationPricing;
use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\Service; // <--- Import the Service model
use App\Models\B2B\Avenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use App\Exceptions\CustomValidationException; // You might want to create a custom exception

class PrestationPricingService
{
    /**
     * Get prestation pricings by avenant ID.
     *
     * @param string $avenantId
     * @return Collection
     */
    public function getPrestationPricingsByAvenantId(string $avenantId): Collection
    {
        return PrestationPricing::with('prestation.service', 'avenant') // Eager load prestation.service
            ->where('avenant_id', $avenantId)
            ->get();
    }
    public function getPrestationPricingsByAnnexId(string $annexid): Collection
    {
        return PrestationPricing::with('prestation.service', 'annex') // Eager load prestation.service
            ->where('annex_id', $annexid)
            ->get();
    }

    /**
     * Get all available service categories.
     *
     * @return Collection
     */
    public function getAllServices(): Collection
    {
        return Service::all();
    }


    /**
     * Get prestations for a specific service ID that are not yet priced for a specific avenant.
     *
     * @param string $serviceId
     * @param string $avenantId
     * @return Collection
     */
    public function getAvailablePrestationsForServiceAndAvenant(string $serviceId, string $avenantId): Collection
    {
        // Get IDs of prestations already priced for this avenant
        $pricedPrestationIds = PrestationPricing::where('avenant_id', $avenantId)
            ->pluck('prestation_id');

        // Get prestations belonging to the given service_id and not in the priced list
        return Prestation::where('service_id', $serviceId)
            ->whereNotIn('id', $pricedPrestationIds)
            ->get();
    }

    /**
     * Create a new prestation pricing entry.
     *
     * @param array $data
     * @return PrestationPricing
     * @throws CustomValidationException
     */
   public function createPrestationPricing(array $data): PrestationPricing
    {
        DB::beginTransaction();
        
        try {
            // Fetch avenant with convention and conventionDetail (not contractData)
            $avenant = Avenant::with('convention.conventionDetail')->find($data['avenant_id']);

            if (!$avenant) {
                throw new ModelNotFoundException('Avenant with ID ' . $data['avenant_id'] . ' not found.');
            }

            // Validate that prestation exists
            $prestation = Prestation::find($data['prestation_id']);
            if (!$prestation) {
                throw new ModelNotFoundException('Prestation with ID ' . $data['prestation_id'] . ' not found.');
            }

            // Check if this combination already exists
            $existingPricing = PrestationPricing::where('avenant_id', $data['avenant_id'])
                ->where('prestation_id', $data['prestation_id'])
                ->where('head', true)
                ->first();

            if ($existingPricing) {
                throw new \Exception('Pricing for this prestation already exists for this avenant.');
            }

            // Get convention detail instead of contractData
            $conventionDetail = $avenant->convention->conventionDetail;

            if (!$conventionDetail) {
                throw new \Exception('Convention detail not found for the associated convention.');
            }

            $globalPrice = $data['prix'];
            $discountPercentage = $conventionDetail->discount_percentage ?? 0;
            $maxPrice = $conventionDetail->max_price ?? 0;

            // Calculate company and patient parts
            $companyShare = $globalPrice * ($discountPercentage / 100);
            $patientShare = $globalPrice - $companyShare;

            $maxPriceExceeded = false;
            if ($maxPrice > 0 && $companyShare > $maxPrice) {
                $excess = $companyShare - $maxPrice;
                $companyShare = $maxPrice;
                $patientShare += $excess;
                $maxPriceExceeded = true;
            }

            // Prepare data for creation
            $pricingData = [
                'avenant_id' => $data['avenant_id'],
                'prestation_id' => $data['prestation_id'],
                'prix' => round($globalPrice, 2),
                'company_price' => round($companyShare, 2),
                'patient_price' => round($patientShare, 2),
                'max_price_exceeded' => $maxPriceExceeded,
                'head' => true,
            ];

            $prestationPricing = PrestationPricing::create($pricingData);
            
            DB::commit();
            
            return $prestationPricing;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Update an existing prestation pricing entry.
     */
    public function updatePrestationPricing(string $id, array $data): PrestationPricing
    {
        $prestationPricing = PrestationPricing::with('avenant.convention.conventionDetail')->findOrFail($id);
        $avenant = $prestationPricing->avenant;
        $conventionDetail = $avenant->convention->conventionDetail;

        if (!$conventionDetail) {
            throw new \Exception('Convention detail not found for the associated convention.');
        }

        $globalPrice = $data['prix'];
        $discountPercentage = $conventionDetail->discount_percentage ?? 0;
        $maxPrice = $conventionDetail->max_price ?? 0;

        $companyShare = $data['company_price'] ?? null;
        $patientShare = $data['patient_price'] ?? null;

        // Recalculate logic...
        $recalculateFromGlobal = false;
        if (!isset($data['company_price']) && !isset($data['patient_price'])) {
            $recalculateFromGlobal = true;
        } else {
             $sumOfParts = ($companyShare ?? 0) + ($patientShare ?? 0);
             if (abs($sumOfParts - $globalPrice) > 0.01) {
                $recalculateFromGlobal = true;
             }
        }

        if ($recalculateFromGlobal) {
            $companyShare = $globalPrice * ($discountPercentage / 100);
            $patientShare = $globalPrice - $companyShare;

            $maxPriceExceeded = false;
            if ($maxPrice > 0 && $companyShare > $maxPrice) {
                $excess = $companyShare - $maxPrice;
                $companyShare = $maxPrice;
                $patientShare += $excess;
                $maxPriceExceeded = true;
            }
        } else {
            $maxPriceExceeded = ($maxPrice > 0 && ($companyShare > $maxPrice || ($globalPrice - $patientShare) > $maxPrice));
        }

        $prestationPricing->update([
            'prix' => round($globalPrice, 2),
            'company_price' => round($companyShare, 2),
            'patient_price' => round($patientShare, 2),
            'max_price_exceeded' => $maxPriceExceeded,
        ]);

        return $prestationPricing;
    }
    public function updatePrestationPricingForAnnex(string $id, array $data): PrestationPricing
    {
        // Load with annex and its related convention and conventionDetail
        $prestationPricing = PrestationPricing::with('annex.convention.conventionDetail')->findOrFail($id);
        
        // Ensure this pricing record is actually tied to an annex
        if (!$prestationPricing->annex) {
            throw new \Exception('The prestation pricing record is not associated with an annex.');
        }

        $annex = $prestationPricing->annex;
        $conventionDetail = $annex->convention->conventionDetail;

        if (!$conventionDetail) {
            throw new \Exception('Convention detail not found for the associated convention (via annex).');
        }

        $globalPrice = $data['prix'];
        $discountPercentage = $conventionDetail->discount_percentage ?? 0;
        $maxPrice = $conventionDetail->max_price ?? 0;

        $companyShare = $data['company_price'] ?? null;
        $patientShare = $data['patient_price'] ?? null;

        // Recalculate logic (identical to avenant update logic)
        $recalculateFromGlobal = false;
        if (!isset($data['company_price']) && !isset($data['patient_price'])) {
            $recalculateFromGlobal = true;
        } else {
             $sumOfParts = ($companyShare ?? 0) + ($patientShare ?? 0);
             if (abs($sumOfParts - $globalPrice) > 0.01) {
                $recalculateFromGlobal = true;
             }
        }

        if ($recalculateFromGlobal) {
            $companyShare = $globalPrice * ($discountPercentage / 100);
            $patientShare = $globalPrice - $companyShare;

            $maxPriceExceeded = false;
            if ($maxPrice > 0 && $companyShare > $maxPrice) {
                $excess = $companyShare - $maxPrice;
                $companyShare = $maxPrice;
                $patientShare += $excess;
                $maxPriceExceeded = true;
            }
        } else {
            // If not recalculating from global, we still need to check max_price_exceeded based on user input
            $maxPriceExceeded = ($maxPrice > 0 && ($companyShare > $maxPrice || ($globalPrice - $patientShare) > $maxPrice));
        }

        $prestationPricing->update([
            'prix' => round($globalPrice, 2),
            'company_price' => round($companyShare, 2),
            'patient_price' => round($patientShare, 2),
            'max_price_exceeded' => $maxPriceExceeded,
        ]);

        return $prestationPricing;
    }
    /**
     * Delete a prestation pricing entry.
     *
     * @param string $id
     * @return bool
     */
    public function deletePrestationPricing(string $id): bool
    {
        return PrestationPricing::destroy($id);
    }
}