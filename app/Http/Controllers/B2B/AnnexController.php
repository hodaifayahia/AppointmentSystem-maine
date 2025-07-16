<?php

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\B2B\Annex; // Import your Annex model
use Illuminate\Validation\ValidationException; // For specific validation error handling
use Illuminate\Support\Facades\Auth; // For authenticated user ID

class AnnexController extends Controller
{
    /**
     * Display a listing of the annexes for a specific contract.
     *
     * @param string $contractId The ID of the convention/contract.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(string $contractId)
    {
        try {
            // Fetch annexes belonging to the given convention_id (contractId)
            // You might want to eager load related models like 'service' if you display service details
            $annexes = Annex::where('convention_id', $contractId)
                            ->orderBy('created_at', 'desc') // Order as per your frontend's display
                            ->get();

            // Optionally, append specialty_name if it comes from a related service
            // This assumes a 'service' relationship on the Annex model and a 'specialty_name' on the Service model
            // If specialty_name is directly on Annex, you don't need this.
            $annexes->each(function ($annex) {
                // Example: If 'service_id' links to a 'Service' model with a 'specialty_name'
                // You'd need to define a 'service' relationship in your Annex model:
                // public function service() { return $this->belongsTo(Service::class); }
                // And then eager load it in the query above: ->with('service')
                // For now, assuming specialty_name is directly available or fetched differently.
                // If it's from a relationship, uncomment and adjust:
                // $annex->specialty_name = $annex->service ? $annex->service->specialty_name : 'N/A';

                // For 'created_by', if it's an ID and you want the name:
                // $user = \App\Models\User::find($annex->created_by);
                // $annex->created_by_name = $user ? $user->name : 'Unknown';
            });


            return response()->json($annexes);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch annexes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created annex in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $contractId The ID of the convention/contract.
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, string $contractId)
    {
        try {
            $validatedData = $request->validate([
                'annex_name' => 'required|string|max:255',
                'specialty_id' => 'required|integer|exists:specialties,id', // Assuming 'specialties' table
                'description' => 'nullable|string',
                'is_active' => 'boolean', // Optional, defaults to true if not provided
                // 'created_by' is set automatically
            ]);

            $annex = Annex::create([
                'annex_name' => $validatedData['annex_name'],
                'convention_id' => $contractId, // Set from route parameter
                'service_id' => $validatedData['specialty_id'], // Assuming specialty_id maps to service_id
                'description' => $validatedData['description'] ?? null,
                'is_active' => $validatedData['is_active'] ?? true, // Default to true
                'created_by' => Auth::id(), // Set current authenticated user's ID
                'updated_by' => Auth::id(), // Also set updated_by on creation
            ]);

            return response()->json($annex, 201); // 201 Created
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], 422); // 422 Unprocessable Entity
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating annex',
                'error' => $e->getMessage()
            ], 500); // 500 Internal Server Error
        }
    }

    /**
     * Display the specified annex.
     *
     * @param string $id The ID of the annex.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        try {
            $annex = Annex::find($id);

            if (!$annex) {
                return response()->json(['message' => 'Annex not found'], 404);
            }

            // Optionally eager load relationships if needed for display
            // $annex->load('service'); // Example

            return response()->json($annex);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve annex',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified annex in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id The ID of the annex.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        try {
            $annex = Annex::find($id);

            if (!$annex) {
                return response()->json(['message' => 'Annex not found'], 404);
            }

            $validatedData = $request->validate([
                'annex_name' => 'required|string|max:255',
                'specialty_id' => 'required|integer|exists:specialties,id', // Assuming 'specialties' table
                'description' => 'nullable|string',
                'is_active' => 'boolean',
                // 'updated_by' is set automatically
            ]);

            $annex->update([
                'annex_name' => $validatedData['annex_name'],
                'service_id' => $validatedData['specialty_id'], // Assuming specialty_id maps to service_id
                'description' => $validatedData['description'] ?? $annex->description, // Keep existing if not provided
                'is_active' => $validatedData['is_active'] ?? $annex->is_active, // Keep existing if not provided
                'updated_by' => Auth::id(), // Set current authenticated user's ID
            ]);

            return response()->json($annex);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating annex',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified annex from storage.
     *
     * @param string $id The ID of the annex.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        try {
            $annex = Annex::find($id);

            if (!$annex) {
                return response()->json(['message' => 'Annex not found'], 404);
            }

            $annex->delete();

            return response()->json(['message' => 'Annex deleted successfully'], 204); // 204 No Content
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting annex',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}