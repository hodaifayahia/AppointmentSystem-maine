<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Allergies; // Make sure this model exists
use Illuminate\Http\Request;
use Illuminate\Routing\Controller; // Ensure this is imported
use Illuminate\Support\Facades\Auth; // For auth()->id()
use Illuminate\Support\Facades\DB;   // For transactions
use Illuminate\Support\Facades\Log; // For error logging

class PatientAllergiesController extends Controller
{
    /**
     * Display a listing of the Allergies for a specific patient.
     *
     * @param  int  $patientId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($patientId)
    {
        try {
            $patient = Patient::findOrFail($patientId); // Use dynamic patientId from the route
            // Fetch Allergies ordered by creation date, newest first
            $Allergies = $patient->Allergies()->orderBy('created_at', 'desc')->get();
            return response()->json($Allergies);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Patient not found for ID: {$patientId}");
            return response()->json(['error' => 'Patient not found.'], 404);
        } catch (\Exception $e) {
            Log::error("Error fetching Allergies for patient {$patientId}: " . $e->getMessage());
            return response()->json(['error' => 'Failed to load Allergies.'], 500);
        }
    }

    /**
     * Store a newly created Allergies in storage.
     * (Kept for completeness, though bulkUpdate is primarily used by the Vue component)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $patientId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $patientId)
    {
        try {
            $patient = Patient::findOrFail($patientId); // Use dynamic patientId

            $validated = $request->validate([
                'content' => 'required|string',
            ]);

            // Ensure the user is authenticated before trying to get their ID
            if (!Auth::check()) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }

            $Allergies = $patient->Allergies()->create([
                'content' => $validated['content'],
                'user_id' => auth()->id(), // Assign the current authenticated user
            ]);

            return response()->json($Allergies, 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Patient not found for ID: {$patientId}");
            return response()->json(['error' => 'Patient not found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("Error storing Allergies for patient {$patientId}: " . $e->getMessage());
            return response()->json(['error' => 'Failed to store Allergies.'], 500);
        }
    }

    /**
     * Handle bulk updates, creations, and deletions of Allergies for a patient.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $patientId
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdate(Request $request, $patientId)
    {
        try {
            $patient = Patient::findOrFail($patientId); // Use dynamic patientId from the route

            $validated = $request->validate([
                'Allergies' => 'required|array',
                'Allergies.*.content' => 'required|string',
                // 'id' is nullable for new Allergies, must exist in DB if provided
                'Allergies.*.id' => 'nullable|integer|exists:Allergies,id',
            ]);

            DB::beginTransaction();

            // Ensure the user is authenticated for creating/updating
            if (!Auth::check()) {
                DB::rollBack();
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }

            // Get existing Allergies IDs for this patient
            $existingIds = $patient->Allergies()->pluck('id')->toArray();
            
            // Get the IDs of Allergies sent in the request (these are the ones to keep/update)
            $newIds = collect($validated['Allergies'])
                ->pluck('id')
                ->filter() // Remove null values (for newly added Allergies)
                ->toArray();

            // Identify and delete Allergies that are no longer present in the request
            $idsToDelete = array_diff($existingIds, $newIds);
            if (!empty($idsToDelete)) {
                // Ensure we only delete Allergies belonging to this specific patient
                $patient->Allergies()->whereIn('id', $idsToDelete)->delete();
            }

            // Process updates and creations
            foreach ($validated['Allergies'] as $AllergiesData) {
                if (isset($AllergiesData['id'])) {
                    // Update existing Allergies for this patient
                    $Allergies = $patient->Allergies()->where('id', $AllergiesData['id'])->first();
                    if ($Allergies) { // Ensure Allergies belongs to this patient
                        $Allergies->update(['content' => $AllergiesData['content']]);
                    } else {
                        // Log if an ID was provided but not found for this patient (should be caught by validation)
                        Log::warning("Allergies with ID {$AllergiesData['id']} not found for patient {$patientId} during bulk update.");
                    }
                } else {
                    // Create new Allergies for this patient
                    $patient->Allergies()->create([
                        'content' => $AllergiesData['content'],
                        'user_id' => auth()->id() // Assign the current authenticated user
                    ]);
                }
            }

            DB::commit();
            
            // Re-fetch the complete, updated list of Allergies for this patient
            // This is crucial for the frontend to re-sync, especially for new IDs
            $finalAllergies = $patient->Allergies()->orderBy('created_at', 'desc')->get();
            return response()->json($finalAllergies, 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error("Patient not found for ID: {$patientId} during bulk update.");
            return response()->json(['error' => 'Patient not found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update Allergies for patient {$patientId}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['error' => 'Failed to update Allergies. Please try again.', 'details' => $e->getMessage()], 500);
        }
    }
}