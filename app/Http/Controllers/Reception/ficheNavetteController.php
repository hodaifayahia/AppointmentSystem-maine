<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reception\ReceptionRequest;
use App\Http\Requests\Reception\ficheNavetteItemRequest;
use App\Http\Resources\Reception\ficheNavetteResource;
use App\Http\Resources\Reception\ficheNavetteItemResource;
use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use App\Services\Reception\ReceptionService;
use Illuminate\Http\Request;

class ficheNavetteController extends Controller
{
    protected $receptionService;

    public function __construct(ReceptionService $receptionService)
    {
        $this->receptionService = $receptionService;
    }

    public function index(Request $request)
    {
        $query = FicheNavette::with(['creator', 'patient', 'items.prestation']);

        // Apply search filter
        if ($request->has('search') && $request->search) {
            $searchTerm = '%' . $request->search . '%';
            $query->whereHas('patient', function($q) use ($searchTerm) {
                $q->where('Firstname', 'like', $searchTerm)
                  ->orWhere('Lastname', 'like', $searchTerm);
            })->orWhere('id', 'like', $searchTerm);
        }

        // Handle pagination
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $ficheNavettes = $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => ficheNavetteResource::collection($ficheNavettes->items()),
            'total' => $ficheNavettes->total(),
            'current_page' => $ficheNavettes->currentPage(),
            'per_page' => $ficheNavettes->perPage(),
            'last_page' => $ficheNavettes->lastPage(),
        ]);
    }

    public function store(ReceptionRequest $request)
    {
        try {
            $fiche = $this->receptionService->createFicheNavette($request->validated());
            return response()->json([
                'success' => true,
                'data' => new ficheNavetteResource($fiche)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(ficheNavette $ficheNavette)
    {
        $ficheNavette->load(['items.prestation', 'patient', 'creator']);
        return response()->json([
            'success' => true,
            'data' => new ficheNavetteResource($ficheNavette)
        ]);
    }

    public function update(ReceptionRequest $request, ficheNavette $ficheNavette)
    {
        try {
            $fiche = $this->receptionService->updateFicheNavette($ficheNavette, $request->validated());
            return response()->json([
                'success' => true,
                'data' => new ficheNavetteResource($fiche)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(ficheNavette $ficheNavette)
    {
        try {
            $this->receptionService->deleteFicheNavette($ficheNavette);
            return response()->json([
                'success' => true,
                'message' => 'Fiche navette supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function changeStatus(Request $request, ficheNavette $ficheNavette)
    {
        $request->validate([
            'status' => 'required|string'
        ]);

        try {
            $fiche = $this->receptionService->changeFicheNavetteStatus(
                $ficheNavette, 
                $request->input('status')
            );
            
            return response()->json([
                'success' => true,
                'data' => new ficheNavetteResource($fiche)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a new prestation to the specified fiche navette.
     */
    public function addPrestation(ficheNavetteItemRequest $request, ficheNavette $ficheNavette)
    {
        try {
            $prestationItem = $this->receptionService->addPrestationToFiche(
                $ficheNavette, 
                $request->validated()
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Prestation ajoutée avec succès',
                'data' => [
                    'prestation_item' => new ficheNavetteItemResource($prestationItem),
                    'fiche' => new ficheNavetteResource($ficheNavette->fresh(['items.prestation', 'patient', 'creator']))
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a prestation item.
     */
    public function updatePrestation(Request $request, ficheNavette $ficheNavette, ficheNavetteItem $item)
    {
        $request->validate([
            'status' => 'nullable|string|in:pending,completed,cancelled,in_progress,required',
            'base_price' => 'nullable|numeric|min:0',
            'final_price' => 'nullable|numeric|min:0',
            'patient_share' => 'nullable|numeric|min:0',
        ]);

        try {
            $item->update($request->only(['status', 'base_price', 'final_price', 'patient_share']));
            
            return response()->json([
                'success' => true,
                'message' => 'Prestation mise à jour avec succès',
                'data' => new ficheNavetteItemResource($item->fresh('prestation'))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a prestation from fiche navette.
     */
    public function removePrestation(ficheNavette $ficheNavette, ficheNavetteItem $item)
    {
        try {
            $item->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Prestation supprimée avec succès',
                'data' => new ficheNavetteResource($ficheNavette->fresh(['items.prestation', 'patient', 'creator']))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
