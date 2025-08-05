<?php

namespace App\Http\Controllers\CONFIGURATION;

use App\Http\Controllers\Controller;
use App\Models\CONFIGURATION\Remise;
use App\Services\CONFIGURATION\RemiseService;
use App\Http\Requests\CONFIGURATION\RemiseRequest;
use Illuminate\Http\Request;
use App\Http\Resources\CONFIGURATION\RemiseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RemiseController extends Controller
{
    protected $remiseService;

    public function __construct(RemiseService $remiseService)
    {
        $this->remiseService = $remiseService;
    }

    /**
     * Display a listing of the resource.
     */
   public function index(Request $request): JsonResponse
    {
        try {
            // Get all request parameters, including 'search', 'page', 'size'
            $params = $request->all();

            // Pass the parameters to the service method
            $remises = $this->remiseService->getAllRemises($params);

            // If using pagination, the resource collection will automatically handle it
            if ($remises instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
                return response()->json([
                    'success' => true,
                    'data' => RemiseResource::collection($remises),
                    'meta' => [ // Include pagination meta data
                        'current_page' => $remises->currentPage(),
                        'from' => $remises->firstItem(),
                        'last_page' => $remises->lastPage(),
                        'per_page' => $remises->perPage(),
                        'to' => $remises->lastItem(),
                        'total' => $remises->total(),
                    ],
                    'message' => 'Remises retrieved successfully'
                ], 200);
            }

            // For non-paginated results (e.g., if 'all' param is always true)
            return response()->json([
                'success' => true,
                'data' => RemiseResource::collection($remises),
                'message' => 'Remises retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error retrieving remises: ' . $e->getMessage(), ['exception' => $e]); // Log the error
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving remises: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RemiseRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $remise = $this->remiseService->createRemise($validatedData);

            return response()->json([
                'success' => true,
                'data' => new RemiseResource($remise),
                'message' => 'Remise created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating remise: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $remise = $this->remiseService->getRemiseById($id);

            if (!$remise) {
                return response()->json([
                    'success' => false,
                    'message' => 'Remise not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new RemiseResource($remise),
                'message' => 'Remise retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving remise: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RemiseRequest $request, Remise $remise): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $updatedRemise = $this->remiseService->updateRemise($remise, $validatedData);

            return response()->json([
                'success' => true,
                'data' => new RemiseResource($updatedRemise),
                'message' => 'Remise updated successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating remise: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Remise $remise): JsonResponse
    {
        try {
            $deleted = $this->remiseService->deleteRemise($remise);

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Remise deleted successfully'
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete remise'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting remise: ' . $e->getMessage()
            ], 500);
        }
    }
}
