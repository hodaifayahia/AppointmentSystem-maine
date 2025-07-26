<?php

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use App\Http\Requests\B2B\StoreConventionRequest;
use App\Http\Requests\B2B\UpdateConventionRequest;
use App\Models\B2B\Convention;
use App\Services\B2B\ConventionService;
use App\Http\Resources\B2B\ConventionResource;
use App\Http\Resources\B2B\PrestationPricingResource;

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
            $organismeId = $request->get('organisme_id'); // Correctly get the organisme_id from query parameters
            // dd($organismeId);
            $query = Convention::with(['conventionDetail', 'organisme', 'annexes']);

            if ($search) {
                $query->where('contract_name', 'like', '%' . $search . '%'); // Assuming 'contract_name' for search
            }

            if ($status) {
                $query->where('status', $status);
            }

            if ($organismeId) {
                $query->where('organisme_id', $organismeId);
            }

            $conventions = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => ConventionResource::collection($conventions)->response()->getData(true)
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
            $formData = [
                'statuses' => ['active', 'inactive', 'pending'],
                'family_auth_options' => [
                    ['label' => 'Ascendant', 'value' => 'ascendant'],
                    ['label' => 'Descendant', 'value' => 'descendant'],
                    ['label' => 'Conjoint', 'value' => 'conjoint'],
                    ['label' => 'Adherent', 'value' => 'adherent'],
                    ['label' => 'Autre', 'value' => 'autre']
                ],
                'organismes' => []
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
            $convention = $this->conventionService->createConvention(
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Convention created successfully',
                'data' => new ConventionResource($convention)
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
            $convention->load(['conventionDetail', 'organisme']);

            return response()->json([
                'success' => true,
                'data' => new ConventionResource($convention)
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
                'data' => new ConventionResource($updatedConvention)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update convention',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function calculatePrestationPricing(Request $request): JsonResponse
    {
        try {
            $pricingData = $this->conventionService->calculatePrestationPricing($request->annex_id);

            return response()->json([
                'success' => true,
                'data' => PrestationPricingResource::collection(collect($pricingData)),
                'message' => 'Pricing calculated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Activate the specified convention.
     * PATCH /conventions/{conventionId}/activate
     */
    public function activate(Request $request, int $conventionId): JsonResponse // <--- This is line 197 (or near it)
    {
        try {
            // Validate the incoming request data
            $request->validate([
                'activationDate' => 'nullable|date_format:Y-m-d',
            ]);

            $activationDate = $request->input('activationDate') ?? Carbon::now()->format('Y-m-d');
            $isDelayedActivation = $request->query('activate_later') === 'yes';

            // Use the ConventionService to handle the activation logic
            $result = $this->conventionService->activateConventionById(
                $conventionId,
                $activationDate,
                $isDelayedActivation
            );

            $message = $isDelayedActivation
                ? 'Convention scheduled for activation successfully'
                : 'Convention activated successfully';

            $updatedConvention = Convention::findOrFail($conventionId);

            return response()->json([
                'message' => $message,
                'data' => new ConventionResource($updatedConvention),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Convention not found',
                'details' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to activate convention',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Terminate the specified convention.
     * PATCH /conventions/{conventionId}/expire
     */
    public function expire($conventionId): JsonResponse
    {
        try {
            $convention = Convention::findOrFail($conventionId);
            $convention->status = 'Terminated'; // Assuming 'Terminated' is the status for expired
            $convention->save();

            return response()->json([
                'success' => true,
                'message' => 'Convention terminated successfully.',
                'data' => new ConventionResource($convention)
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Convention not found.',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to terminate convention.',
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

            $convention->conventionDetail()->delete();

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