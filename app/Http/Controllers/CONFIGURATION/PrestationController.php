<?php

namespace App\Http\Controllers\CONFIGURATION;

use App\Http\Controllers\Controller; // Import the base Controller class
use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\Service;
use App\Models\CONFIGURATION\ModalityType;
use App\Http\Requests\CONFIGURATION\PrestationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Specialization;
use App\Http\Resources\PrestationResource;
use App\Imports\PrescriptionsImport;
// import Log
use Log;
use Maatwebsite\Excel\Facades\Excel;



class PrestationController extends Controller
{
    /**
     * Display a listing of prestations
     */
    public function index(Request $request)
    {
        try {
            $query = Prestation::with(['service', 'specialization', 'modalityType']);

            // Add search functionality
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('internal_code', 'like', "%{$search}%")
                      ->orWhere('billing_code', 'like', "%{$search}%");
                });
            }

            // Add filtering by service
            if ($request->has('service_id')) {
                $query->where('service_id', $request->get('service_id'));
            }

            // Add filtering by type
            if ($request->has('type')) {
                $query->where('type', $request->get('type'));
            }

            // Add filtering by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->get('is_active'));
            }

            $prestations = $query->paginate($request->get('per_page', 15));
            return PrestationResource::collection($prestations);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching prestations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created prestation
     */
    public function store(PrestationRequest $request)
    {
        try {
            DB::beginTransaction();

            $prestationData = $this->preparePrestationData($request);
            $prestation = Prestation::create($prestationData);

            DB::commit();

            return response()->json([
                'message' => 'Prestation created successfully',
                'data' => new PrestationResource($prestation->load(['service', 'specialization', 'modalityType']))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error creating prestation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified prestation
     */
    public function show($id)
    {
        try {
            $prestation = Prestation::with(['service', 'specialization', 'modalityType'])->findOrFail($id);
            return new PrestationResource($prestation);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Prestation not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
   

    /**
     * Update the specified prestation
     */
   public function update(PrestationRequest $request, $id)
{
    try {
        $prestation = Prestation::findOrFail($id);

        DB::beginTransaction();

        $prestationData = $this->preparePrestationData($request);
        
        // Update the main prestation data
        $prestation->update($prestationData);

        // Handle medications update if they exist in the request
        if ($request->has('medications')) {
            $currentMedicationIds = $prestation->medications->pluck('id')->toArray();
            $incomingMedicationIds = [];

            foreach ($request->medications as $medicationData) {
                if (isset($medicationData['id'])) {
                    // Update existing medication
                    $medication = PrestationMedication::find($medicationData['id']);
                    if ($medication && $medication->prestation_id === $prestation->id) {
                        $medication->update([
                            'medication_id' => $medicationData['medication_id'],
                            'form' => $medicationData['form'],
                            'num_times' => $medicationData['num_times'],
                            'frequency' => $medicationData['frequency'],
                            'start_date' => $medicationData['start_date'] ?? null,
                            'end_date' => $medicationData['end_date'] ?? null,
                            'period_intakes' => $medicationData['period_intakes'] ?? null,
                            'timing_preference' => $medicationData['timing_preference'] ?? null,
                            'frequency_period' => $medicationData['frequency_period'] ?? null,
                            'description' => $medicationData['description'] ?? null,
                            // Add custom pill counts
                            'pills_matin' => $medicationData['pills_matin'] ?? null,
                            'pills_apres_midi' => $medicationData['pills_apres_midi'] ?? null,
                            'pills_midi' => $medicationData['pills_midi'] ?? null,
                            'pills_soir' => $medicationData['pills_soir'] ?? null
                        ]);
                        $incomingMedicationIds[] = $medicationData['id'];
                    }
                } else {
                    // Create new medication
                    $newMedication = $prestation->medications()->create([
                        'medication_id' => $medicationData['medication_id'],
                        'form' => $medicationData['form'],
                        'num_times' => $medicationData['num_times'],
                        'frequency' => $medicationData['frequency'],
                        'start_date' => $medicationData['start_date'] ?? null,
                        'end_date' => $medicationData['end_date'] ?? null,
                        'period_intakes' => $medicationData['period_intakes'] ?? null,
                        'timing_preference' => $medicationData['timing_preference'] ?? null,
                        'frequency_period' => $medicationData['frequency_period'] ?? null,
                        'description' => $medicationData['description'] ?? null,
                        // Add custom pill counts
                        'pills_matin' => $medicationData['pills_matin'] ?? null,
                        'pills_apres_midi' => $medicationData['pills_apres_midi'] ?? null,
                        'pills_midi' => $medicationData['pills_midi'] ?? null,
                        'pills_soir' => $medicationData['pills_soir'] ?? null
                    ]);
                    $incomingMedicationIds[] = $newMedication->id;
                }
            }

            // Delete medications that are no longer in the incoming list
            $medicationsToDelete = array_diff($currentMedicationIds, $incomingMedicationIds);
            if (!empty($medicationsToDelete)) {
                PrestationMedication::whereIn('id', $medicationsToDelete)->delete();
            }
        }

        DB::commit();

        return response()->json([
            'message' => 'Prestation updated successfully',
            'data' => new PrestationResource($prestation->fresh(['service', 'specialization', 'modalityType', 'medications.medication']))
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Error updating prestation',
            'error' => $e->getMessage()
        ], 500);
    }
}
     public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file', // Added mimes for security and correct file types
        ]);

        try {
            $import = new PrescriptionsImport();
            Excel::import($import, $request->file('file'));

            // Retrieve failures after import attempt using your custom getter
            $failures = $import->getFailures(); // <--- CORRECTED LINE

            if (!empty($failures)) {
                $errorMessages = [];
                foreach ($failures as $failure) {
                    $row = $failure['row'];
                    $attribute = $failure['attribute'];
                    $errors = implode(', ', $failure['errors']);
                    $errorMessages[] = "Row {$row}, Column '{$attribute}': {$errors}";
                }
                return response()->json([
                    'message' => 'Some prescriptions could not be imported due to validation errors.',
                    'failures' => $errorMessages
                ], 422); // Unprocessable Entity
            }

            return response()->json(['message' => 'Prescriptions imported successfully!']);

        } catch (ValidationException $e) { // Use the specific ValidationException
            $failures = $e->failures();
            $errorMessages = [];
            foreach ($failures as $failure) {
                $row = $failure->row();
                $attribute = $failure->attribute();
                $errors = implode(', ', $failure->errors());
                $errorMessages[] = "Row {$row}, Column '{$attribute}': {$errors}";
            }
            Log::error('Excel Import Validation Error (caught by ValidationException):', ['errors' => $errorMessages]);
            return response()->json([
                'message' => 'Validation errors occurred during import.',
                'errors' => $errorMessages
            ], 422);

        } catch (\Exception $e) {
            Log::error('Excel Import General Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'An unexpected error occurred during import: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Remove the specified prestation
     */
    public function destroy($id)
    {
        try {
            $prestation = Prestation::findOrFail($id);
            $prestation->delete();

            return response()->json([
                'message' => 'Prestation deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting prestation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get services for form options
     */
    public function getServices()
    {
        try {
            $services = Service::where('is_active', true)
                              ->select('id', 'name')
                              ->orderBy('name')
                              ->get();

            return response()->json($services);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching services',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specializations for form options
     */
    public function getSpecializations()
    {
        try {
            $specializations = Specialization::select('id', 'name')
                                           ->orderBy('name')
                                           ->get();

            return response()->json([
                'data' => $specializations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching specializations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get modality types for form options
     */
    public function getModalityTypes()
    {
        try {
            $modalityTypes = ModalityType::where('is_active', true)
                                       ->select('id', 'name')
                                       ->orderBy('name')
                                       ->get();

            return response()->json([
                'data' => $modalityTypes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching modality types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prepare prestation data for storage
     */
    private function preparePrestationData(PrestationRequest $request)
    {
        $data = $request->validated();

        // Handle JSON fields
        $jsonFields = ['non_applicable_discount_rules', 'required_prestations_info', 'required_consents'];
        foreach ($jsonFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = is_array($data[$field]) ? json_encode($data[$field]) : $data[$field];
            }
        }

        // Handle night tariff
        if (!$request->has('night_tariff') || $request->night_tariff === null) {
            $data['night_tariff'] = null;
        }

        // Handle hospitalization nights
        if (!$request->requires_hospitalization) {
            $data['default_hosp_nights'] = null;
        }

        return $data;
    }

    /**
     * Toggle prestation active status
     */
    public function toggleStatus($id)
    {
        try {
            $prestation = Prestation::findOrFail($id);
            $prestation->is_active = !$prestation->is_active;
            $prestation->save();

            return response()->json([
                'message' => 'Prestation status updated successfully',
                'data' => new PrestationResource($prestation)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating prestation status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
     public function getFilterOptions()
    {
        try {
            $services = Service::select('id', 'name')
                               ->orderBy('name')
                               ->get();

            $specializations = Specialization::select('id', 'name')
                                             ->orderBy('name')
                                             ->get();

            $modalityTypes = ModalityType::select('id', 'name')
                                         ->orderBy('name')
                                         ->get();

            // Define payment types as they appear to be static in the frontend
            $paymentTypes = [
                ['value' => 'cash', 'label' => 'Cash'],
                ['value' => 'card', 'label' => 'Card'],
                ['value' => 'insurance', 'label' => 'Insurance'],
                ['value' => 'bank_transfer', 'label' => 'Bank Transfer'],
                // Add any other payment types as needed
            ];

            return response()->json([
                'services' => $services,
                'specializations' => $specializations,
                'payment_types' => $paymentTypes,
                'modality_types' => $modalityTypes,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching filter options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get prestation statistics
     */
    public function getStatistics()
    {
        try {
            $stats = [
                'total_prestations' => Prestation::count(),
                'active_prestations' => Prestation::where('is_active', true)->count(),
                'medical_prestations' => Prestation::where('type', 'Médical')->count(),
                'surgical_prestations' => Prestation::where('type', 'Chirurgical')->count(),
                'average_price' => Prestation::avg('public_price'),
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
