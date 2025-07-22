<?php

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\AllergyController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AppointmentAvailableMonthController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentForcerController;
use App\Http\Controllers\AppointmentStatus;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\ChronicDiseaseController;
use App\Http\Controllers\ConsulationController;
use App\Http\Controllers\ConsultationworkspacesController;
use App\Http\Controllers\DashboradController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\ExcludedDates;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FamilyHistoryController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\ImportanceEnumController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\MedicationDoctorFavoratController;
use App\Http\Controllers\OpinionRequestController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PlaceholderController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\specializationsController;
use App\Http\Controllers\SurgicalController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\WaitListController;
use App\Http\Controllers\CONFIGURATION\ServiceController;
use App\Http\Controllers\CONFIGURATION\ModalityTypeController;
use App\Http\Controllers\CONFIGURATION\ModalityController;
use App\Http\Controllers\CONFIGURATION\PrestationController;
use App\Http\Controllers\CRM\OrganismeController;
use App\Http\Controllers\INFRASTRUCTURE\PavilionController;
use App\Http\Controllers\INFRASTRUCTURE\RoomTypeController;
use App\Http\Controllers\INFRASTRUCTURE\RoomController;
use App\Http\Controllers\INFRASTRUCTURE\BedController;
use App\Http\Controllers\INFRASTRUCTURE\InfrastructureDashboardController;
use App\Http\Controllers\B2B\ConventionController;
use App\Http\Controllers\B2B\AgreementsController;
use App\Http\Controllers\B2B\ConventionDetailController;
use App\Http\Controllers\B2B\AnnexController;
use App\Http\Controllers\B2B\PrestationPricingController;
use App\Http\Controllers\B2B\AvenantController;
use App\Http\Controllers\CRM\OrganismeContactController;

use Illuminate\Support\Facades\Route;














// Redirect root URL to the login page if the user is not authenticated
Route::get('/', function () {
    return redirect('/login');
})->middleware('guest');
Route::get('/', function () {
    return redirect('/dashboard'); // or whatever your main authenticated page is
})->middleware('auth');
// Routes for all authenticated users
Route::middleware(['auth'])->group(function () {

    // User Routes
    Route::get('/api/users', [UserController::class, 'index']);
    Route::get('/api/users/receptionist', [UserController::class, 'GetReceptionists']);
    Route::post('/api/users', [UserController::class, 'store']);
    
    Route::delete('/api/users', [UserController::class, 'bulkDelete'])->name('users.bulkDelete');
    Route::get('/api/users/search', [UserController::class, 'search'])->name('users.search');
    Route::put('/api/users/{userid}', [UserController::class, 'update']);
    Route::delete('/api/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::patch('/api/users/{userid}/change-role', [UserController::class, 'ChangeRole'])->name('users.ChangeRole');
    
    Route::get('/api/loginuser', [UserController::class, 'getCurrentUser']);
    Route::get('/api/role', [UserController::class, 'role']);

    Route::apiResource('/api/roles', RoleController::class);

    // Doctor Routes
    // Route::get('/api/doctors/specializ/{specializationId}', [DoctorController::class, 'GetDoctorsBySpecilaztionsthisisfortest']);
    Route::get('/api/doctors', [DoctorController::class, 'index']);
    Route::post('/api/doctors', [DoctorController::class, 'store']);
    Route::put('/api/doctors/{doctorid}', [DoctorController::class, 'update']);
    Route::delete('/api/doctors/{doctorid}', [DoctorController::class, 'destroy']);
    Route::delete('/api/doctors', [DoctorController::class, 'bulkDelete']);
    Route::get('/api/doctors/search', [DoctorController::class, 'search']);
    Route::get('/api/doctors/WorkingDates', [DoctorController::class, 'WorkingDates']);
    Route::get('/api/doctors/{doctorId}', [DoctorController::class, 'getDoctor']);
    Route::get('/api/doctors/handel/specific', [DoctorController::class, 'getspecific']);
    Route::get('/api/doctors/specializations/{specializationId}', [DoctorController::class, 'GetDoctorsBySpecilaztionsthisisfortest']);
    
    // Specializations Routes
    Route::get('/api/specializations', [specializationsController::class, 'index']);
    Route::post('/api/specializations', [specializationsController::class, 'store']);
    Route::put('/api/specializations/{id}', [specializationsController::class, 'update']);
    Route::delete('/api/specializations/{id}', [specializationsController::class, 'destroy']);
    
    // Appointment Routes
    Route::get('/api/appointments/search', [AppointmentController::class, 'search']);
    Route::get('/api/appointments/checkAvailability', [AppointmentController::class, 'checkAvailability']);
    Route::get('/api/appointments/canceledappointments', [AppointmentController::class, 'getAllCanceledAppointments']);
    Route::get('/api/appointments/available', [AppointmentController::class, 'AvailableAppointments']);
    Route::get('/api/appointmentStatus/{doctorid}', [AppointmentStatus::class, 'appointmentStatus']);
    Route::get('/api/appointmentStatus/patient/{patientid}', [AppointmentStatus::class, 'appointmentStatusPatient']);
    Route::get('/api/todaysAppointments/{doctorid}', [AppointmentStatus::class, 'todaysAppointments']);
    Route::get('/api/appointments/ForceSlots', [AppointmentController::class, 'ForceAppointment']);
    Route::get('/api/appointments/{doctorid}', [AppointmentController::class, 'index']);
    Route::get('/api/appointments/consulationappointment/{doctorid}', [AppointmentController::class, 'consulationappointment']);
    
    Route::get('/api/appointments/patient/{Patientid}', [AppointmentController::class, 'ForPatient']);
    Route::get('/api/appointments/{doctorId}/filter-by-date', [AppointmentController::class, 'filterByDate']);
    Route::patch('/api/appointment/{appointmentId}/status', [AppointmentController::class, 'changeAppointmentStatus']);
    Route::post('/api/appointments', [AppointmentController::class, 'store']);
    Route::get('/api/appointments', [AppointmentController::class, 'GetAllAppointments']);
    Route::put('/api/appointments/{appointmentid}', [AppointmentController::class, 'update']);
    Route::get('/api/appointments/{doctorId}/{appointmentId}', [AppointmentController::class, 'getAppointment']);
    Route::delete('/api/appointments/{appointmentid}', [AppointmentController::class, 'destroy']);
    Route::post('/api/appointment/nextappointment/{appointmentid}', [AppointmentController::class, 'nextAppointment']);
    Route::get('/api/appointment/pending', [AppointmentController::class, 'getPendingAppointment']);
    
    // Schedule Routes
    Route::get('/api/schedules/{doctorid}', [ScheduleController::class, 'index']);
    Route::put('/api/schedules/{doctorid}', [ScheduleController::class, 'updateSchedule']);
    Route::delete('/api/schedules/{doctorid}', [ScheduleController::class, 'destroy']);

    // Patient Routes
    Route::get('/api/patients', [PatientController::class, 'index']);
    Route::post('/api/patients', [PatientController::class, 'store']);
    Route::put('/api/patients/{patientid}', [PatientController::class, 'update']);
    Route::get('/api/patient/{PatientId}', [PatientController::class, 'PatientAppointments']);
    Route::get('/api/patients/search', [PatientController::class, 'search']);
    Route::get('/api/patients/{parentid}', [PatientController::class, 'SpecificPatient']);
    Route::delete('/api/patients/{patientid}', [PatientController::class, 'destroy']);
    
    Route::post('/api/appointments/Confirmation/print-confirmation-ticket', [AppointmentController::class, 'printConfirmationTicket']);
    Route::post('/generate-appointments-pdf', [AppointmentController::class, 'generateAppointmentsPdf']);
    Route::post('/api/appointments/print-ticket', [AppointmentController::class, 'printTicket']);
    
    
    Route::get('/api/setting/user', [SettingController::class, 'index']);
    Route::put('/api/setting/user', [SettingController::class, 'update']);
    Route::put('/api/setting/password', [SettingController::class, 'updatePassword']);


    // Waitlist Routes
    Route::post('/api/waitlists', [WaitListController::class, 'store']);
    Route::post('/api/waitlists/count/{doctorid}', [WaitListController::class, 'countForYouAndNotForYou']);
    Route::put('/api/waitlists/{waitlist}', [WaitListController::class, 'update']);
    Route::get('/api/waitlists', [WaitListController::class, 'index']);
    Route::get('/api/waitlists/ForDoctor', [WaitListController::class, 'GetForDoctor']);
    Route::get('/api/waitlists/search', [WaitListController::class, 'search']);
    Route::get('/waitlists/filter', [WaitlistController::class, 'filter']);
    Route::post('/api/waitlists/{waitlist}/add-to-appointments', [WaitListController::class, 'AddPaitentToAppointments']);
    Route::post('/api/waitlists/{waitlistid}/move-to-end', [WaitListController::class, 'moveToend']);
    Route::delete('/api/waitlists/{waitlist}', [WaitListController::class, 'destroy']); 
    Route::patch('/api/waitlists/{waitlist}/importance', [WaitlistController::class, 'updateImportance']);
    Route::get('/api/waitlist/empty', [WaitListController::class, 'isempty']);


    Route::get('/api/importance-enum', [ImportanceEnumController::class, 'index']);


    // api for the permisson for forceappontment
    Route::get('/api/doctor-user-permissions', [AppointmentForcerController::class, 'getPermissions']);
    Route::get('/api/doctor-user-permissions/ability', [AppointmentForcerController::class, 'IsAbleToForce']);
    Route::post('/api/doctor-user-permissions', [AppointmentForcerController::class, 'updateOrCreatePermission']);


    // Alternatively, define routes manually
    Route::get('/api/excluded-dates/{doctorId}', [ExcludedDates::class, 'index']);
    // Route::get('/api/excluded-dates', [ExcludedDates::class, 'GetExcludedDates']);
    Route::post('/api/excluded-dates', [ExcludedDates::class, 'store']);
    Route::put('/api/excluded-dates/{id}', [ExcludedDates::class, 'update']);
    Route::delete('/api/excluded-dates/delete-by-date', [ExcludedDates::class, 'destroyByDate']);
    Route::get('/api/monthwork/{doctorid}', [AppointmentAvailableMonthController::class, 'index']);
    
    // Logout Route for Importing and Exporting Data to Excel 
    
    
        //routes for attributes 
        //consultation
   Route::prefix('/api/')->group(function () {
    Route::resource('placeholders', PlaceholderController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    // REMOVE or comment out this line:
    // Route::get('placeholders/search', [PlaceholderController::class, 'search']);

    Route::post('placeholders/consultation-attributes/save', [PlaceholderController::class, 'saveConsultationAttributes']);
    Route::get('placeholders/consultation-attributes/search-values', [AttributeController::class, 'searchAttributeValues']);
});
        // Medicales
        Route::apiResource('/api/medications', MedicationController::class);


        Route::apiResource('/api/opinion-requests', OpinionRequestController::class);
        Route::post('/api/opinion-requests/{id}/reply', [OpinionRequestController::class, 'reply']);

        Route::post('/api/medications/toggle-favorite', [MedicationDoctorFavoratController::class, 'toggleFavorite']);
        Route::apiResource('/api/favorate', MedicationDoctorFavoratController::class);

        Route::post('/api/attributes', [AttributeController::class, 'store']);
        Route::get('/api/attributes/{id}', [AttributeController::class, 'index']);
        Route::put('/api/attributes/{id}', [AttributeController::class, 'update']);
        Route::delete('/api/attributes/{id}', [AttributeController::class, 'destroy']);
        Route::get('/api/attributes/search', [AttributeController::class, 'search']); 
        Route::get('/api/attributes/metadata', [AttributeController::class, 'getMetadata']); 
        // make the same but make it for consulations
        // consulations

        Route::post('/api/consulations', [ConsulationController::class, 'store']);
        // In your API routes file
        Route::get('/api/consultations/{consultationId}', [ConsulationController::class, 'show']);
        Route::get('/api/consultations/by-appointment/{appointmentid}', [ConsulationController::class, 'getConsultationByAppointmentId']);

        Route::get('/api/consulations/{patientid}', [ConsulationController::class, 'index']);
        // Route::post('/api/consultation/generate-word', [ConsulationController::class, 'Generateword']);
        // Route::post('/api/consultation/generate-pdf', [ConsulationController::class, 'GenerateDocuments']);
        Route::put('/api/consultations/{id}', [ConsulationController::class, 'update']);
        Route::delete('/api/consulations/{id}', [ConsulationController::class, 'destroy']);
        Route::get('/api/consulations/search', [ConsulationController::class, 'search']); 

Route::get('/templates/content', [ConsulationController::class, 'getTemplateContent']);
Route::post('/consultation/convert-to-pdf', [ConsulationController::class, 'convertToPdf']);
        Route::apiResource('/api/templates', TemplateController::class);
        Route::get('/api/templates/search', [TemplateController::class, 'search']);

        Route::post('/api/import/users', [ImportController::class, 'ImportUsers']);
        Route::get('/api/export/users', [ExportController::class, 'ExportUsers']);

        Route::post('/api/import/Patients', [ImportController::class, 'ImportPatients']);
        Route::get('/api/export/Patients', [ExportController::class, 'ExportPatients']);

        
        Route::post('/api/import/placeholders', [ImportController::class, 'Importplaceholders']);
        Route::get('/api/export/placeholders', [ExportController::class, 'Exportplaceholders']);

        Route::post('/api/import/attributes', [ImportController::class, 'ImportAttributes']);
        Route::get('/api/export/attributes', [ExportController::class, 'ExportAttributes']);

        Route::post('/api/import/appointment/{doctorid}', [ImportController::class, 'ImportAppointment']);
        Route::get('/api/export/appointment', [ExportController::class, 'ExportAppointment']);
        Route::get('/api/medical-dashboard', [DashboradController::class, 'index']);

// folser 

        Route::apiResource('/folders', FolderController::class);
        
        Route::get('folders/{folder}/templates', [FolderController::class, 'getTemplates']);
        Route::get('folders/search', [FolderController::class, 'search']);

        Route::prefix('/api/consultation')->group(function () {
            // patient consulation
            Route::get('/patients/{patientid}', [ConsulationController::class, 'GetPatientConsulationIndex']);
            Route::post('/generate-word', [ConsulationController::class, 'generateWord']);
             Route::get('/{consultationId}', [ConsulationController::class, 'show']);

            Route::post('/generate-pdf', [ConsulationController::class, 'generatePdf']);
            Route::post('/convert-to-pdf', [ConsulationController::class, 'convertToPdf']);
            Route::post('/{patientId}/save-pdf', [ConsulationController::class, 'savePdf']);
            Route::get('/{patientId}/documents', [ConsulationController::class, 'GetPatientConsultaionDoc']);

    

            });
        Route::get('/api/placeholders/consultation/{appointmentid}/attributes', [PlaceholderController::class, 'getConsultationPlaceholderAttributes']);
  // Medicales
        Route::apiResource('/api/medications', MedicationController::class);
        Route::apiResource('/api/consultationworkspaces', ConsultationworkspacesController::class);
        Route::get('/api/consultationworkspaces/search', [ConsultationworkspacesController::class, 'search']);
        Route::get('/api/details/consultationworkspaces', [ConsultationworkspacesController::class, 'getworkspaceDetails']);
        Route::post('/api/details/consultationworkspaces', [ConsultationworkspacesController::class, 'storeworkDetails']);
        Route::delete('/api/details/consultationworkspaces', [ConsultationworkspacesController::class, 'DeleteworkspaceDetails']);

        Route::post('/api/medications/toggle-favorite', [MedicationDoctorFavoratController::class, 'toggleFavorite']);

// Group all API routes under a prefix for better organization
Route::prefix('/api/prescription')->group(function () {
    // Resource route for CRUD operations on prescriptions
    Route::resource('/', PrescriptionController::class);
    Route::get('/{id}/download-pdf', [PrescriptionController::class, 'downloadPrescriptionPdf']);
    Route::get('/prescription-templates', [PrescriptionController::class, 'getPrescriptionTemplates']);
    Route::post('/prescription-templates', [PrescriptionController::class, 'prescriptiontemplates']);
      Route::get('/download', [PrescriptionController::class, 'downloadPdf']);
    Route::get('/view/{appointment_id}', [PrescriptionController::class, 'viewPdf']);
        Route::get('/print', [PrescriptionController::class, 'printPrescription']);

Route::get('prescription-templates/{templateId}/medications', [PrescriptionController::class, 'getTemplateMedications']);
});


            // servicecontroller 
        Route::apiResource('/api/services', ServiceController::class);

        Route::apiResource('/api/modality-types', ModalityTypeController::class);
        Route::apiResource('/api/modalities', ModalityController::class);
        Route::apiResource('/api/prestation', PrestationController::class);
// In your routes/api.php or web.php
Route::prefix('/api')->group(function () {
    // Get annexes for a specific contract
    Route::get('/annex/contract/{contractId}', [AnnexController::class, 'getByContract']);
    
    // Standard resource routes
    Route::apiResource('annex', AnnexController::class);
    
    // Custom route for creating annex with contractId
    Route::post('/annex/{contractId}', [AnnexController::class, 'storeWithContract']);
});
           // --- Prescription Management ---

 Route::prefix('/api/prestation')->group(function () {
                // Register resource routes directly
        // Route::apiResource('/', PrestationController::class);
        Route::post('/import', [PrestationController::class, 'import']);

        Route::get('/filter-options', [PrestationController::class, 'getFilterOptions']);


        // Add custom routes after
        Route::get('/statistics', [PrestationController::class, 'getStatistics']);
        Route::patch('/{id}/toggle-status', [PrestationController::class, 'toggleStatus']);

    });
  // --- Patient Consultation History (Nested Resources) ---
    // Group routes by patient to utilize nested resources and route model binding for patient history
    Route::prefix('api/consultation/patients/{patient}')->group(function () { // Added 'history' for semantic clarity
        // Allergies
        Route::apiResource('allergies', AllergyController::class);

        // Chronic Diseases
        Route::apiResource('chronic-diseases', ChronicDiseaseController::class);

        // Family History
        Route::apiResource('family-history', FamilyHistoryController::class);

        // Surgical History
        Route::apiResource('surgical-history', SurgicalController::class);
    });


Route::prefix('modalities')->group(function () {
    // Get filter options for dropdowns
    Route::get('filter-options', [ModalityController::class, 'getFilterOptions']);
    
    // Advanced search endpoint
    Route::post('advanced-search', [ModalityController::class, 'advancedSearch']);
    
    // Export functionality
    Route::get('export', [ModalityController::class, 'export']);
    
    // Dropdown data endpoints
    Route::get('dropdown/modality-types', [ModalityController::class, 'getModalityTypesForDropdown']);
    Route::get('dropdown/physical-locations', [ModalityController::class, 'getPhysicalLocationsForDropdown']);
    Route::get('dropdown/services', [ModalityController::class, 'getServicesForDropdown']);
});









// API Routes
Route::prefix('api')->group(function () {
    Route::get('/dashboard/infrastructure/stats', [InfrastructureDashboardController::class, 'stats']);
    Route::get('/infrastructure/recent-activity', [InfrastructureDashboardController::class, 'recentActivity']);
    
});



//Organism 

Route::get('/api/organismes/settings', [OrganismeController::class, 'OrganismesSettings']);
Route::apiResource('/api/organismes',OrganismeController::class);
Route::apiResource('/api/organisme-contacts', OrganismeContactController::class);

Route::apiResource('/api/pavilions', PavilionController::class);
Route::apiResource('/api/conventions', ConventionController::class);
Route::patch('/api/conventions/{conventionId}/activate', [ConventionController::class, 'activate']);
Route::patch('/api/conventions/{conventionId}/expire', [ConventionController::class, 'expire']); // Ensure this exists if you use it

Route::get('/api/prestation/annex/price', [PrescriptionController::class, 'getAnnexPrestation']);
Route::post('/api/organismes/settings', [ConventionController::class, 'activateConvenation']);
Route::apiResource('/api/agreements', AgreementsController::class);
Route::apiResource('/api/rooms', ConventionController::class);
Route::apiResource('/api/rooms', PavilionController::class);
Route::apiResource('/api/rooms', RoomController::class);

// Routes for ConventionDetailController
Route::prefix('/api/convention/agreementdetails')->group(function () {
    // Get details for a specific convention
    Route::get('{conventionId}', [ConventionDetailController::class, 'getDetailsByConvention']);
    // Update a specific detail for a convention
    Route::put('{conventionId}/{detailId}', [ConventionDetailController::class, 'update']);

    // Get details for a specific avenant
    Route::get('avenant/{avenantId}', [ConventionDetailController::class, 'getDetailsByAvenant']);
    // Update a specific detail for an avenant
    Route::put('avenant/{avenantId}/{detailId}', [ConventionDetailController::class, 'update']);
});
Route::get('/api/pavilions/{pavilionId}/services', [PavilionController::class, 'PavilionServices']);

Route::apiResource('/api/room-types', RoomTypeController::class);
Route::apiResource('/api/beds', BedController::class);
Route::get('/api/beds/availablerooms', [BedController::class, 'getAvailableRooms']);

// Add these new routes for Prestation Pricing
Route::prefix('/api/prestation-pricings')->group(function () {
    Route::get('/', [PrestationPricingController::class, 'index']); // To get all pricings for an annex
    Route::post('/', [PrestationPricingController::class, 'store']); // To get all pricings for an annex
    Route::put('/{id}', [PrestationPricingController::class, 'update']); // To update a specific pricing
    Route::patch('/{id}', [PrestationPricingController::class, 'update']); // Alias for update
    Route::delete('/{id}', [PrestationPricingController::class, 'destroy']); // Alias for update
    // Add other routes like show, destroy if needed
    // Route::get('/{id}', [PrestationPricingController::class, 'show']);
    // Route::delete('/{id}', [PrestationPricingController::class, 'destroy'])
    Route::get('/avenant/{avenantId}', [PrestationPricingController::class, 'getPrestationsByAvenantId']);


});
Route::get('prestations/available-for-avenant/{avenantId}', [PrestationPricingController::class, 'getAvailablePrestations']);
Route::get('/api/prestations/available-for-service-avenant/{serviceId}/{avenantId}', [PrestationPricingController::class, 'getAvailablePrestationsForServiceAndAvenant']); // Specific filter


Route::prefix('/api/avenants')->group(function () {
    // Changed contractId to conventionId
    Route::post('/convention/{conventionId}/duplicate', [AvenantController::class, 'createAvenantAndDuplicatePrestations']);
    Route::patch('/{avenantId}/activate', [AvenantController::class, 'activateAvenant']);
    Route::get('/{avenantId}', [AvenantController::class, 'getAvenantById']);
    // Changed contractId to conventionId
    Route::get('/convention/{conventionId}/pending', [AvenantController::class, 'checkPendingAvenantByConventionId']);
    // Changed contractId to conventionId
    Route::get('/convention/{conventionId}', [AvenantController::class, 'getAvenantsByConventionId']);

});


// Catch-all route for views
Route::get('{view}', [ApplicationController::class, '__invoke'])
    ->where('view', '(.*)');

});
Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

