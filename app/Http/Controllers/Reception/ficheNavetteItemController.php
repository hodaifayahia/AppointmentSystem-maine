<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Resources\Reception\ficheNavetteResource;
use App\Http\Resources\Reception\ficheNavetteItemResource;
use App\Models\Reception\ficheNavette;
use App\Services\Reception\ReceptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ficheNavetteItemController extends Controller
{
    protected $receptionService;

    public function __construct(ReceptionService $receptionService)
    {
        $this->receptionService = $receptionService;
    }

    /**
     * Store items to an existing fiche navette
     */
    public function store(Request $request, $ficheNavetteId): JsonResponse
    {
        // dd($ficheNavetteId);
        // Debug to check if ficheNavetteId is being passed correctly
        if (!$ficheNavetteId) {
            return response()->json([
                'success' => false,
                'message' => 'Fiche Navette ID is required',
                'error' => 'Missing ficheNavetteId parameter'
            ], 400);
        }

        $validatedData = $request->validate([
            'type' => 'required|string|in:prestation,custom',
            'prestations' => 'sometimes|array',
            'prestations.*.id' => 'required|exists:prestations,id',
            'prestations.*.doctor_id' => 'nullable|exists:users,id',
            'prestations.*.quantity' => 'nullable|integer|min:1',
            'prestations.*.isPackage' => 'nullable|boolean',
            'dependencies' => 'sometimes|array',
            'dependencies.*.id' => 'required|exists:prestations,id',
            'dependencies.*.doctor_id' => 'nullable|exists:users,id',
            'customPrestations' => 'sometimes|array',
            'customPrestations.*.id' => 'nullable|exists:prestations,id',
            'customPrestations.*.doctor_id' => 'nullable|exists:users,id',
            'customPrestations.*.selected_doctor_id' => 'nullable|exists:users,id',
            'customPrestations.*.quantity' => 'nullable|integer|min:1',
            'customPrestations.*.display_name' => 'nullable|string',
            'customPrestations.*.type' => 'nullable|string|in:predefined,custom',
            'selectedSpecialization' => 'nullable|exists:specializations,id',
            'selectedDoctor' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
            'packages' => 'sometimes|array',
            'packages.*.id' => 'required|exists:prestation_packages,id'
        ]);

        try {
            // Get the existing fiche navette

            // dd($ficheNavetteId);
            $ficheNavette = ficheNavette::findOrFail($ficheNavetteId);
            
            // Add items to the existing fiche navette (remove the extra parameter)
            $updatedFiche = $this->receptionService->addItemsToFicheNavette($ficheNavette, $validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Items added to Fiche Navette successfully',
                'data' => new ficheNavetteResource($updatedFiche->load([
                    'items.prestation', 
                    'items.dependencies.dependencyPrestation', // Updated to use dependencyPrestation
                    'patient', 
                    'creator'
                ]))
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add items to Fiche Navette',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get items for a specific fiche navette
     */
    public function index(Request $request, $ficheNavetteId): JsonResponse
    {
        try {
            $ficheNavette = ficheNavette::with([
                'items.prestation.specialization', 
                'items.dependencies.dependencyPrestation.specialization', // Load dependencies with prestations
                'items.doctor',
                'patient',
                'creator'
            ])->findOrFail($ficheNavetteId);

            // Check if items exist
            if ($ficheNavette->items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No items found for this fiche navette',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => ficheNavetteItemResource::collection($ficheNavette->items)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch fiche navette items',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a specific item in a fiche navette
     */
    public function update(Request $request, $ficheNavetteId, $itemId): JsonResponse
    {
        $validatedData = $request->validate([
            'doctor_id' => 'nullable|exists:users,id',
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled,required,dependency',
            'custom_name' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        try {
            $updatedFiche = $this->receptionService->updateFicheNavetteItem($ficheNavetteId, $itemId, $validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Item updated successfully',
                'data' => new ficheNavetteResource($updatedFiche)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove an item from a fiche navette
     */
    public function destroy($ficheNavetteId, $itemId): JsonResponse
    {
        try {
            $updatedFiche = $this->receptionService->removeFicheNavetteItem($ficheNavetteId, $itemId);

            return response()->json([
                'success' => true,
                'message' => 'Item removed successfully',
                'data' => new ficheNavetteResource($updatedFiche)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}