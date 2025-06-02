<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use App\Models\PrescriptionMedication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // Needed for saving and deleting files
use App\Models\Patient;
use App\Models\Consultation;
use App\Models\User;
use App\Models\Doctor;
use PDF; // Barryvdh\DomPDF\Facade\Pdf
use Illuminate\Support\Facades\DB; // For database transactions

class PrescriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     * Optionally filter by patient_id.
     */
    public function index(Request $request)
    {
        $prescriptions = Prescription::with(['medications', 'patient', 'doctor'])
            ->when($request->patient_id, function($query, $patientId) {
                return $query->where('patient_id', $patientId);
            })
            ->latest()
            ->get();

        return response()->json($prescriptions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id', // Make patient_id required for new prescriptions
            'patient_age' => 'nullable|numeric|min:0',
            'patient_weight' => 'nullable|numeric|min:0',
            'medications' => 'required|array|min:1',
            'medications.*.cdActiveSubstance' => 'required|string|max:255',
            'medications.*.brandName' => 'nullable|string|max:255',
            'medications.*.pharmaceuticalForm' => 'required|string|max:255',
            'medications.*.dosePerIntake' => 'required|string|max:255',
            'medications.*.numIntakesPerDay' => 'required|string|max:255',
            'medications.*.durationOrBoxes' => 'required|string|max:255',
            'appointment_id' => 'nullable|exists:appointments,id'
        ]);

        // IMPORTANT: Replace this hardcoded doctor ID with actual authentication logic.
        $doctor = Doctor::with('user')->where('id', 1)->first(); // Example: auth()->user()->doctor;

        if (!$doctor) {
            return response()->json(['error' => 'Doctor not found or not associated with logged-in user.'], 404);
        }

        $patient = Patient::find($validated['patient_id']);
        if (!$patient) {
            return response()->json(['error' => 'Patient not found.'], 404);
        }

        // Update patient details if provided
        $patient->age = $validated['patient_age'] ?? $patient->age;
        $patient->weight = $validated['patient_weight'] ?? $patient->weight;
        $patient->save();

        // Get consultationId dynamically based on appointment_id
        $consultationId = null;
        if (isset($validated['appointment_id'])) {
            $consultation = Consultation::where('appointment_id', $validated['appointment_id'])->first();
            if ($consultation) {
                $consultationId = $consultation->id;
            }
        }

        DB::beginTransaction(); // Start a database transaction

        try {
            $prescription = Prescription::create([
                'consultation_id' => $consultationId,
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id, // Ensure doctor_id is stored with the prescription
                'signature_status' => 'confirmed',
                'pdf_path' => null, // Temporarily null, will be updated after PDF generation
            ]);

            foreach ($validated['medications'] as $medication) {
                $prescription->medications()->create([
                    'cd_active_substance' => $medication['cdActiveSubstance'],
                    'brand_name' => $medication['brandName'] ?? null,
                    'pharmaceutical_form' => $medication['pharmaceuticalForm'],
                    'dose_per_intake' => $medication['dosePerIntake'],
                    'num_intakes_per_day' => $medication['numIntakesPerDay'],
                    'duration_or_boxes' => $medication['durationOrBoxes']
                ]);
            }

            // Load relationships needed for PDF generation
            $prescription->load('medications', 'patient', 'doctor.user');

            // Generate and save PDF
            $pdfPath = $this->generateAndSavePrescriptionPdf($prescription);
            $prescription->pdf_path = $pdfPath; // Update the PDF path
            $prescription->save(); // Save the prescription with the updated PDF path

            DB::commit(); // Commit the transaction

            return response()->json([
                'success' => true,
                'message' => 'Prescription created successfully.',
                'prescription' => $prescription // Already loaded relationships
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback on error
            return response()->json(['error' => 'Failed to create prescription.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $prescription = Prescription::with(['medications', 'patient', 'doctor.user'])->findOrFail($id);
        return response()->json($prescription);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Prescription $prescription)
    {
        $validated = $request->validate([
            'patient_age' => 'nullable|numeric|min:0',
            'patient_weight' => 'nullable|numeric|min:0',
            'medications' => 'nullable|array|min:1', // Make nullable for partial updates
            'medications.*.cdActiveSubstance' => 'required_with:medications.*|string|max:255', // Use correct naming for validation
            'medications.*.brandName' => 'nullable|string|max:255',
            'medications.*.pharmaceuticalForm' => 'required_with:medications.*|string|max:255',
            'medications.*.dosePerIntake' => 'required_with:medications.*|string|max:255',
            'medications.*.numIntakesPerDay' => 'required_with:medications.*|string|max:255',
            'medications.*.durationOrBoxes' => 'required_with:medications.*|string|max:255',
            'appointment_id' => 'nullable|exists:appointments,id' // Allow updating appointment ID
        ]);

        // IMPORTANT: Implement proper authorization here (e.g., check if current doctor owns this prescription)
        // if (auth()->user()->doctor->id !== $prescription->doctor_id) {
        //     return response()->json(['error' => 'Unauthorized.'], 403);
        // }

        DB::beginTransaction(); // Start a database transaction

        try {
            // Update patient details if provided
            $patient = $prescription->patient;
            if ($patient) {
                $patient->age = $validated['patient_age'] ?? $patient->age;
                $patient->weight = $validated['patient_weight'] ?? $patient->weight;
                $patient->save();
            }

            // Update consultation ID if provided
            if (isset($validated['appointment_id'])) {
                $consultation = Consultation::where('appointment_id', $validated['appointment_id'])->first();
                $prescription->consultation_id = $consultation ? $consultation->id : null;
            }

            $prescription->save(); // Save prescription changes (like consultation_id)

            // Handle Medications update: Delete existing and re-create if 'medications' array is provided
            if (isset($validated['medications'])) {
                $prescription->medications()->delete(); // Remove all old medications
                foreach ($validated['medications'] as $medication) {
                    $prescription->medications()->create([
                        'cd_active_substance' => $medication['cdActiveSubstance'], // Use correct key names
                        'brand_name' => $medication['brandName'] ?? null,
                        'pharmaceutical_form' => $medication['pharmaceuticalForm'],
                        'dose_per_intake' => $medication['dosePerIntake'],
                        'num_intakes_per_day' => $medication['numIntakesPerDay'],
                        'duration_or_boxes' => $medication['durationOrBoxes']
                    ]);
                }
            }

            // Before generating a new PDF, delete the old one if it exists
            if ($prescription->pdf_path) {
                Storage::disk('public')->delete($prescription->pdf_path);
            }

            // Load relationships needed for PDF generation (after medication updates)
            $prescription->load('medications', 'patient', 'doctor.user');

            // Generate and save the new PDF
            $newPdfPath = $this->generateAndSavePrescriptionPdf($prescription);
            $prescription->pdf_path = $newPdfPath; // Update with the new PDF path
            $prescription->save(); // Save the prescription with the new PDF path

            DB::commit(); // Commit the transaction

            return response()->json([
                'success' => true,
                'message' => 'Prescription updated successfully',
                'prescription' => $prescription // Already loaded relationships
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback on error
            return response()->json(['error' => 'Failed to update prescription.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Prescription $prescription)
    {
        DB::beginTransaction();
        try {
            // Delete associated PDF file
            if ($prescription->pdf_path) {
                Storage::disk('public')->delete($prescription->pdf_path);
            }

            // Medications will be cascade deleted if setup in migration, otherwise delete them first
            $prescription->medications()->delete();
            $prescription->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Prescription deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete prescription.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Generates a PDF for a given prescription and saves it to storage.
     *
     * @param Prescription $prescription
     * @return string The path to the saved PDF file.
     */
    protected function generateAndSavePrescriptionPdf(Prescription $prescription): string
    {
        // Ensure relationships are loaded for the view
        $prescription->loadMissing('medications', 'patient', 'doctor.user');

        $data = [
            'prescription' => $prescription,
            'doctor_name' => $prescription->doctor->user->name ?? 'Médecin Inconnu',
            'patient_first_name' => $prescription->patient->first_name,
            'patient_last_name' => $prescription->patient->last_name,
            'current_date' => now()->format('d/m/Y'),
            'medications' => $prescription->medications,
        ];

        $pdf = PDF::loadView('prescriptions.pdf', $data);

        $filename = "prescriptions/pdfs/ordonnance_{$prescription->id}_{$prescription->patient->last_name}_" . time() . ".pdf";
        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Generates and downloads a PDF for a given prescription.
     */
    public function downloadPrescriptionPdf($id)
    {
        $prescription = Prescription::with(['medications', 'patient', 'doctor.user'])->findOrFail($id);

        // If the PDF is already generated and stored, you can stream it directly
        if ($prescription->pdf_path && Storage::disk('public')->exists($prescription->pdf_path)) {
            $filename = "ordonnance_{$prescription->id}_{$prescription->patient->last_name}.pdf";
            return Storage::disk('public')->download($prescription->pdf_path, $filename);
        }

        // Otherwise, generate it on the fly (and maybe save it for future use)
        // For simplicity, we're just streaming it here.
        $data = [
            'prescription' => $prescription,
            'doctor_name' => $prescription->doctor->user->name ?? 'Médecin Inconnu',
            'patient_first_name' => $prescription->patient->first_name,
            'patient_last_name' => $prescription->patient->last_name,
            'current_date' => now()->format('d/m/Y'),
            'medications' => $prescription->medications,
        ];
        $pdf = PDF::loadView('prescriptions.pdf', $data);

        $filename = "ordonnance_{$prescription->id}_{$prescription->patient->last_name}.pdf";
        return $pdf->stream($filename);
    }

    /**
     * Generates and streams a PDF for a given prescription.
     * This is essentially the same as downloadPrescriptionPdf if it's just streaming.
     * You might want to merge these two methods if they have identical functionality.
     */
    public function printPrescription($id)
    {
        return $this->downloadPrescriptionPdf($id); // Reuse the download logic
    }
}