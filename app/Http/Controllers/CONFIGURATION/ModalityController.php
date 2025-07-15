<?php

namespace App\Http\Controllers\CONFIGURATION;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CONFIGURATION\Modality;
use App\Models\CONFIGURATION\ModalityType;
use App\Models\CONFIGURATION\Room;
use App\Models\CONFIGURATION\Service;
use App\Http\Resources\CONFIGURATION\ModalityResource;
use Illuminate\Validation\Rule;

class ModalityController extends Controller
{
    /**
     * Display a listing of the modalities with search and filter capabilities.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Modality::with(['modalityType', 'service']);

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('internal_code', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('dicom_ae_title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('ip_address', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('integration_protocol', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('data_retrieval_method', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('modalityType', function ($q) use ($searchTerm) {
                      $q->where('name', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('service', function ($q) use ($searchTerm) {
                      $q->where('name', 'LIKE', "%{$searchTerm}%");
                  });
            });
        }

        // Filter by modality type
        if ($request->filled('modality_type_id')) {
            $query->where('modality_type_id', $request->modality_type_id);
        }

        // Filter by service
        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        // Filter by operational status
        if ($request->filled('operational_status')) {
            $query->where('operational_status', $request->operational_status);
        }

        // Filter by physical location
        if ($request->filled('physical_location_id')) {
            $query->where('physical_location_id', $request->physical_location_id);
        }

        // Filter by integration protocol
        if ($request->filled('integration_protocol')) {
            $query->where('integration_protocol', 'LIKE', "%{$request->integration_protocol}%");
        }

        // Filter by data retrieval method
        if ($request->filled('data_retrieval_method')) {
            $query->where('data_retrieval_method', 'LIKE', "%{$request->data_retrieval_method}%");
        }

        // Date range filters
        if ($request->filled('created_from')) {
            $query->where('created_at', '>=', $request->created_from);
        }

        if ($request->filled('created_to')) {
            $query->where('created_at', '<=', $request->created_to . ' 23:59:59');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        // Validate sort fields
        $allowedSortFields = ['name', 'internal_code', 'created_at', 'updated_at', 'operational_status'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $query->orderBy($sortBy, $sortDirection);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $perPage = min(max($perPage, 1), 100); // Limit between 1 and 100

        $modalities = $query->paginate($perPage);

        return ModalityResource::collection($modalities);
    }

    /**
     * Get filter options for dropdowns and search.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilterOptions()
    {
        $modalityTypes = ModalityType::select('id', 'name')->get();
        $services = Service::select('id', 'name')->get();
       $Rooms = Room::select('id', 'name')->get();
        
        // Get unique values for certain fields
        $protocols = Modality::whereNotNull('integration_protocol')
            ->where('integration_protocol', '!=', '')
            ->distinct()
            ->pluck('integration_protocol');
            
       
        return response()->json([
            'modality_types' => $modalityTypes,
            'services' => $services,
           // 'physical_locations' => $Rooms,
            'protocols' => $protocols,
            'operational_statuses' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive']
            ]
        ]);
    }

    /**
     * Advanced search with multiple criteria.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function advancedSearch(Request $request)
    {
        $query = Modality::with(['modalityType', 'service']);

        // Build complex search queries
        $searchCriteria = $request->get('criteria', []);
        
        foreach ($searchCriteria as $criteria) {
            $field = $criteria['field'] ?? null;
            $operator = $criteria['operator'] ?? 'like';
            $value = $criteria['value'] ?? null;
            
            if (!$field || !$value) continue;
            
            switch ($operator) {
                case 'equals':
                    $query->where($field, $value);
                    break;
                case 'like':
                    $query->where($field, 'LIKE', "%{$value}%");
                    break;
                case 'starts_with':
                    $query->where($field, 'LIKE', "{$value}%");
                    break;
                case 'ends_with':
                    $query->where($field, 'LIKE', "%{$value}");
                    break;
                case 'greater_than':
                    $query->where($field, '>', $value);
                    break;
                case 'less_than':
                    $query->where($field, '<', $value);
                    break;
                case 'between':
                    if (is_array($value) && count($value) === 2) {
                        $query->whereBetween($field, $value);
                    }
                    break;
            }
        }

        $modalities = $query->paginate($request->get('per_page', 10));
        return ModalityResource::collection($modalities);
    }

    /**
     * Store a newly created modality in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:modalities,name',
            'internal_code' => 'nullable|string|max:255|unique:modalities,internal_code',
            'modality_type_id' => 'required|exists:modality_types,id',
            'dicom_ae_title' => 'nullable|string|max:255',
            'port' => 'nullable|integer',
'physical_location_id' => 'nullable|exists:rooms,id',
            'operational_status' => 'required',
            'service_id' => 'nullable|exists:services,id',
            'integration_protocol' => 'nullable|string|max:255',
            'connection_configuration' => 'nullable|string',
            'data_retrieval_method' => 'nullable|string|max:255',
            'ip_address' => 'nullable|ip',
        ]);

        // $validatedData['operational_status'] = $validatedData['operational_status'] ? 'active' : 'inactive';

        $modality = Modality::create($validatedData);
        
        return response()->json([
            'message' => 'Modality created successfully.',
            'data' => new ModalityResource($modality->load(['modalityType', 'service']))
        ], 201);
    }

    /**
     * Display the specified modality.
     *
     * @param  \App\Models\CONFIGURATION\Modality  $modality
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Modality $modality)
    {
        return new ModalityResource($modality->load(['modalityType', 'service']));
    }

    /**
     * Update the specified modality in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CONFIGURATION\Modality  $modality
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Modality $modality)
    {
        $validatedData = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('modalities')->ignore($modality->id),
            ],
            'internal_code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('modalities')->ignore($modality->id),
            ],
            'modality_type_id' => 'required|exists:modality_types,id',
            'dicom_ae_title' => 'nullable|string|max:255',
            'port' => 'nullable|integer',
'physical_location_id' => 'nullable|exists:rooms,id',
            'operational_status' => 'required',
            'service_id' => 'nullable|exists:services,id',
            'integration_protocol' => 'nullable|string|max:255',
            'connection_configuration' => 'nullable|string',
            'data_retrieval_method' => 'nullable|string|max:255',
            'ip_address' => 'nullable|ip',
        ]);

        // $validatedData['operational_status'] = $validatedData['operational_status'] ? 'active' : 'inactive';

        $modality->update($validatedData);

        return response()->json([
            'message' => 'Modality updated successfully.',
            'data' => new ModalityResource($modality->load(['modalityType', 'service']))
        ]);
    }
    

    /**
     * Remove the specified modality from storage.
     *
     * @param  \App\Models\CONFIGURATION\Modality  $modality
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Modality $modality)
    {
        $modality->delete();

        return response()->json(['message' => 'Modality deleted successfully.']);
    }

    /**
     * Get a list of modality types for dropdowns.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getModalityTypesForDropdown()
    {
        $modalityTypes = ModalityType::select('id', 'name')->get();
        return response()->json($modalityTypes);
    }

    /**
     * Get a list of physical locations for dropdowns.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRoomsForDropdown()
    {
        $Rooms = Room::select('id', 'name')->get();
        return response()->json($Rooms);
    }

    /**
     * Get a list of services for dropdowns.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getServicesForDropdown()
    {
        $services = Service::select('id', 'name')->get();
        return response()->json($services);
    }

    /**
     * Export modalities based on current filters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function export(Request $request)
    {
        // This would typically generate a CSV or Excel file
        // For now, we'll return the data in JSON format
        $query = Modality::with(['modalityType', 'service']);

        // Apply the same filters as the index method
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('internal_code', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('dicom_ae_title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('ip_address', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Apply other filters...
        if ($request->filled('modality_type_id')) {
            $query->where('modality_type_id', $request->modality_type_id);
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        if ($request->filled('operational_status')) {
            $query->where('operational_status', $request->operational_status);
        }

        $modalities = $query->get();

        return response()->json([
            'message' => 'Export data retrieved successfully.',
            'data' => ModalityResource::collection($modalities),
            'total' => $modalities->count()
        ]);
    }
}