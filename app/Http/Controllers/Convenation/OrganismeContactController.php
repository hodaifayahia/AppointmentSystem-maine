<?php

namespace App\Http\Controllers\Convenation;

use App\Models\Convenation\OrganismeContact;
use App\Models\Convenation\Organisme;
use Illuminate\Http\Request;

class OrganismeContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contacts = OrganismeContact::with('organisme')->latest()->paginate(10);
        return view('organisme_contacts.index', compact('contacts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $organismes = Organisme::all();
        return view('organisme_contacts.create', compact('organismes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'organisme_id' => 'required|exists:organismes,id',
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'role' => 'nullable|string|max:255',
        ]);

        OrganismeContact::create($validated);

        return redirect()->route('organisme-contacts.index')
                         ->with('success', 'Contact created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(OrganismeContact $organismeContact)
    {
        return view('organisme_contacts.show', compact('organismeContact'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OrganismeContact $organismeContact)
    {
        $organismes = Organisme::all();
        return view('organisme_contacts.edit', compact('organismeContact', 'organismes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OrganismeContact $organismeContact)
    {
        $validated = $request->validate([
            'organisme_id' => 'required|exists:organismes,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'role' => 'nullable|string|max:255',
        ]);

        $organismeContact->update($validated);

        return redirect()->route('organisme-contacts.index')
                         ->with('success', 'Contact updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrganismeContact $organismeContact)
    {
        $organismeContact->delete();

        return redirect()->route('organisme-contacts.index')
                         ->with('success', 'Contact deleted successfully.');
    }
}