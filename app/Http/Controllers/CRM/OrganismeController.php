<?php

namespace App\Http\Controllers\CRM;

use App\Models\CRM\Organisme;
use App\Http\Controllers\Controller;

use App\Http\Requests\CRM\OrganismeStoreRequest; // Import the store request
use App\Http\Requests\CRM\OrganismeUpdateRequest; // Import the update request
use App\Http\Resources\CRM\OrganisemResource; // Import the resource

use Illuminate\Http\Request; // Keep if you need for index/show, otherwise can remove
use Illuminate\Validation\Rule; // No longer strictly needed for this controller, but good practice to keep the use statement if Rule is used elsewhere

class OrganismeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $organismes = Organisme::all();
        return response()->json($organismes);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\CRM\OrganismeStoreRequest  $request
     */
    public function store(OrganismeStoreRequest $request)
    {
        $validatedData = $request->validated();

        $organisme = Organisme::create($validatedData);
        return response()->json($organisme, 201); // 201 Created
    }

    /**
     * Display the specified resource.
     */
    public function show(Organisme $organisme)
    {
        return response()->json($organisme);
    }


    public function OrganismesSettings()
{
    $organisme = Organisme::first(); // or however you get your data
    
    if (!$organisme) {
        return response()->json([
            'message' => 'No settings found',
            'data' => null
        ], 404);
    }
    
    return response()->json([
        'message' => 'Settings retrieved successfully',
        'data' => new OrganisemResource($organisme)
    ]);
}

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\CRM\OrganismeUpdateRequest  $request
     * @param  \App\Models\Organisme  $organisme
     */
    public function update(OrganismeUpdateRequest $request, Organisme $organisme)
    {
        // The validation logic is now handled by OrganismeUpdateRequest.
        $validatedData = $request->validated();

        $organisme->update($validatedData);
        return response()->json($organisme);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organisme $organisme)
    {
        $organisme->delete();
        return response()->json(null, 204); // 204 No Content
    }
}