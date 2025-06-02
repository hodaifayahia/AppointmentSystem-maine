<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Template;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PatientDocement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Events\PdfGeneratedEvent;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Barryvdh\DomPDF\Options;
use Dompdf\Dompdf;

use PhpOffice\PhpWord\Shared\Html;
use App\Models\Consultation;
use ZipArchive;
//import log
use Log;
use Spatie\Browsershot\Browsershot; // Add this import

class ConsulationController extends Controller
{
  
    // public function savePdf(Request $request, $patientId)
    // {
    //     // Validate the incoming request
    //     $request->validate([
    //         'pdf_file' => 'required|mimes:pdf|max:20480', // Max 20MB, adjust as needed
    //     ]);

    //     try {
    //         // Get the uploaded file
    //         $pdfFile = $request->file('pdf_file');

    //         // Generate a unique file name
    //         $fileName = 'consultation_' . $patientId . '_' . time() . '.' . $pdfFile->getClientOriginalExtension();

    //         // Define the storage path: storage/app/public/consultation/patientId/
    //         // Make sure the 'public' disk is configured in config/filesystems.php
    //         // And you have run 'php artisan storage:link'
    //         $path = "consultation/{$patientId}/{$fileName}";

    //         // Store the file
    //         Storage::disk('public')->put($path, file_get_contents($pdfFile));

    //         // Optionally, save the file path to a database (e.g., in a 'consultations' table)
    //         // You would typically have a Consultation model here
    //         // Consultation::create([
    //         //     'patient_id' => $patientId,
    //         //     'document_type' => 'pdf',
    //         //     'file_path' => $path, // Save the public path
    //         //     'original_name' => $pdfFile->getClientOriginalName(),
    //         // ]);

    //         return response()->json(['success' => true, 'message' => 'PDF saved successfully!', 'path' => Storage::url($path)]);

    //     } catch (\Exception $e) {
    //         return response()->json(['success' => false, 'message' => 'Error saving PDF: ' . $e->getMessage()], 500);
    //     }
    // }

    /**
 * Save PDF document uploaded from frontend
 */

public function savePdf(Request $request)
{
    try {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf',
            'template_ids' => 'required|string',
            'placeholder_data' => 'required|string',
            'patient_id' => 'required|numeric',
            'appointment_id' => 'nullable|numeric', // Optional, if you have an appointment ID
        ]);
        // initilaze the vlaue of appointment_id and patient_id
        $appointmentId = 1;
        // Save the PDF file
        $pdfPath = $request->file('pdf_file')->store('consultation-pdfs', 'public');
        
        // Decode the JSON strings
        $templateIds = json_decode($request->template_ids, true);
        $placeholderData = json_decode($request->placeholder_data, true);
        
        // Get authenticated doctor's ID
        $doctorId = auth()->id();
        
        // Get patient ID from request
        $patientId = $request->patient_id;
        
        // Get appointment ID (you'll need to modify this based on your needs)
        $appointmentId = $request->appointment_id ?? null;

        // Dispatch the PdfGeneratedEvent
        event(new PdfGeneratedEvent(
            $templateIds,
            $placeholderData,
            $pdfPath,
            $doctorId,
            $patientId,
            1
        ));

        return response()->json([
            'success' => true,
            'path' => $pdfPath,
            'message' => 'PDF saved successfully'
        ]);

    } catch (\Exception $e) {
        \Log::error('PDF save error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to save PDF: ' . $e->getMessage()
        ], 500);
    }
}

public function GetPatientConsultaionDoc($patientId)
{
    try {
        $documents = PatientDocement::where('patient_id', $patientId)
                                    ->orderBy('created_at', 'desc')
                                    ->get();

        return response()->json([
            'success' => true,
            'data' => $documents
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve documents: ' . $e->getMessage()
        ], 500);
    }
}
    

}