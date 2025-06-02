<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FolderController extends Controller
{
    public function index()
    {
        $folders = Folder::all();
        return response()->json(['data' => $folders]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'doctor_id' => 'nullable|exists:doctors,id',
            'specializations_id' => 'nullable|exists:specializations,id',
            'description' => 'nullable|string'
        ]);
        // iniitlize the doctor_id and specializations_id
        $doctor_id = 1;
        $specializations_id =1;

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $folder = Folder::create($request->all());
        return response()->json(['data' => $folder], 201);
    }

    public function update(Request $request, Folder $folder)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $folder->update($request->all());
        return response()->json(['data' => $folder]);
    }

    public function destroy(Folder $folder)
    {
        $folder->delete();
        return response()->json(null, 204);
    }

    public function search(Request $request)
    {
        $query = $request->get('query', '');
        
        $folders = Folder::where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->get();

        return response()->json(['data' => $folders]);
    }
}
