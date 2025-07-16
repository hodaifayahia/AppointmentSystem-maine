<?php

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\B2B\ConventionDetail; // Make sure to import your model
use Illuminate\Validation\ValidationException;

class ConventionDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     * This method will likely be called with convention_id or avenant_id
     */
    public function index(Request $request)
    {
        $query = ConventionDetail::query();

        if ($request->has('convention_id')) {
            $query->where('convention_id', $request->input('convention_id'));
        }

        if ($request->has('avenant_id')) {
            // Assuming ConventionDetail has an 'avenant_id' column or a relationship
            // If avenant_id is part of the convention table and not directly in convention_details
            // you might need to adjust this join or relationship.
            $query->where('avenant_id', $request->input('avenant_id'));
        }

        $details = $query->get();

        return response()->json($details);
    }

    /**
     * Store a newly created resource in storage. (Optional, if you allow adding details)
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'convention_id' => 'required|exists:conventions,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'family_auth' => 'nullable|string',
                'max_price' => 'nullable|numeric|min:0',
                'min_price' => 'nullable|numeric|min:0|lte:max_price', // min_price should be <= max_price
                'discount_percentage' => 'nullable|numeric|min:0|max:100',
                'avenant_id' => 'nullable|exists:avenants,id', // Adjust if 'avenants' table exists
            ]);

            $detail = ConventionDetail::create($validatedData);

            return response()->json($detail, 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation Error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error storing convention detail', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $detail = ConventionDetail::find($id);

        if (!$detail) {
            return response()->json(['message' => 'Convention detail not found'], 404);
        }

        return response()->json($detail);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $detail = ConventionDetail::where('convention_id',$id);

            if (!$detail) {
                return response()->json(['message' => 'Convention detail not found'], 404);
            }

            $validatedData = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'family_auth' => 'nullable|string',
                'max_price' => 'nullable|numeric|min:0',
                'min_price' => 'nullable|numeric|min:0|lte:max_price',
                'discount_percentage' => 'nullable|numeric|min:0|max:100',
                'avenant_id' => 'nullable|exists:avenants,id', // Only if avenant_id can be updated
            ]);

            $detail->update($validatedData);

            return response()->json($detail);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation Error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error updating convention detail', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage. (Optional, if you allow deleting details)
     */
    public function destroy(string $id)
    {
        $detail = ConventionDetail::find($id);

        if (!$detail) {
            return response()->json(['message' => 'Convention detail not found'], 404);
        }

        $detail->delete();

        return response()->json(['message' => 'Convention detail deleted successfully'], 204);
    }

    // Custom method to fetch details for a specific convention (as per your Vue component's call)
    public function getDetailsByConvention(string $conventionId)
    {
        $details = ConventionDetail::where('convention_id', $conventionId)->get();
        return response()->json($details);
    }

    // Custom method to fetch details for a specific avenant (as per your Vue component's call)
    public function getDetailsByAvenant(string $avenantId)
    {
        $details = ConventionDetail::where('avenant_id', $avenantId)->get();
        return response()->json($details);
    }
}