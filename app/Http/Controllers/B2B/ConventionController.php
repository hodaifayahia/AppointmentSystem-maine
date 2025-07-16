<?php

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use App\Http\Requests\B2B\StoreConventionRequest;
use App\Http\Requests\B2B\UpdateConventionRequest;
use App\Models\B2B\Convention;
use App\Services\B2B\ConventionService;
use App\Http\Resources\B2B\ConventionResource; // Import the new resource

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConventionController extends Controller
{
    public function __construct(
        private ConventionService $conventionService
    ) {}

    /**
     * Display a listing of the resource.
     * GET /conventions
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $status = $request->get('status');
            $organismeId = $request->get('organisme_id');

            // Always eager load conventionDetail when fetching conventions for the API
        $query = Convention::with(['conventionDetail', 'organisme']); // <-- Add 'organisme' here

            // Apply filters
            if ($search) {
                // Assuming 'name' is the field for contract_name in Convention model
                $query->where('name', 'like', '%' . $search . '%');
            }

            if ($status) {
                $query->where('status', $status);
            }

            if ($organismeId) {
                $query->where('organisme_id', $organismeId);
            }

            $conventions = $query->paginate($perPage);

            // Use the ConventionResource to transform the paginated collection
            return response()->json([
                'success' => true,
                'data' => ConventionResource::collection($conventions)->response()->getData(true) // Get data directly from pagination
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve conventions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     * GET /conventions/create
     */
    public function create(): JsonResponse
    {
        try {
            // Return form data or configuration needed for creating a convention
            $formData = [
                'statuses' => ['active', 'inactive', 'pending'],
                'family_auth_options' => [
                    ['label' => 'Ascendant', 'value' => 'ascendant'],
                    ['label' => 'Descendant', 'value' => 'descendant'],
                    ['label' => 'Conjoint', 'value' => 'conjoint'],
                    ['label' => 'Adherent', 'value' => 'adherent'],
                    ['label' => 'Autre', 'value' => 'autre']
                ],
                // You can add organismes list here
                'organismes' => [] // Fetch from database as needed
            ];

            return response()->json([
                'success' => true,
                'data' => $formData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load create form data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /conventions
     */
    public function store(StoreConventionRequest $request ): JsonResponse
    {
        try {
            $convention = $this->conventionService->createConvention( // Assuming this returns a single Convention model
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Convention created successfully',
                'data' => new ConventionResource($convention) // Use 'make' or 'new' for a single model
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create convention',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /conventions/{convention}
     */
    public function show(Convention $convention): JsonResponse
    {
        try {
        $convention->load(['conventionDetail', 'organisme']); // <-- Add 'organisme' here

            return response()->json([
                'success' => true,
                'data' => new ConventionResource($convention) // Use 'make' or 'new' for a single model
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve convention',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     * PUT/PATCH /conventions/{convention}
     */
    public function update(UpdateConventionRequest $request, Convention $convention): JsonResponse
    {
        try {
            $updatedConvention = $this->conventionService->updateConvention(
                $convention,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Convention updated successfully',
                'data' => new ConventionResource($updatedConvention) // Use 'make' or 'new' for a single model
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update convention',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /conventions/{convention}
     */
    public function destroy(Convention $convention): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Delete related convention details first (if not using cascading delete)
            $convention->conventionDetail()->delete();

            // Delete the convention
            $convention->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Convention deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete convention',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}