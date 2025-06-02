<?php
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AppointmentAvailableMonthController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentForcerController;
use App\Http\Controllers\AppointmentStatus;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\ConsulationController;
use App\Http\Controllers\DashboradController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\ExcludedDates;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportanceEnumController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PlaceholderController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\specializationsController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaitListController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\PatientAllergiesController;
use App\Http\Controllers\PrescriptionController;
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
    Route::get('/api/doctors/specializations/{specializationId}', [DoctorController::class, 'GetDoctorsBySpecilaztions']);
    
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
        
        //placeholser 
        Route::post('/api/placeholders', [PlaceholderController::class, 'store']);
        Route::get('/api/placeholders', [PlaceholderController::class, 'index']);
        Route::put('/api/placeholders/{id}', [PlaceholderController::class, 'update']);
        Route::delete('/api/placeholders/{id}', [PlaceholderController::class, 'destroy']);
        Route::get('/api/placeholders/search', [PlaceholderController::class, 'search']);

        Route::post('/api/attributes', [AttributeController::class, 'store']);
        Route::get('/api/attributes/{id}', [AttributeController::class, 'index']);
        Route::put('/api/attributes/{id}', [AttributeController::class, 'update']);
        Route::delete('/api/attributes/{id}', [AttributeController::class, 'destroy']);
        Route::get('/api/attributes/search', [AttributeController::class, 'search']); 
        // make the same but make it for consulations
        // consulations

        Route::post('/api/consulations', [ConsulationController::class, 'store']);
        Route::get('/api/consulations', [ConsulationController::class, 'index']);
        // Route::post('/api/consultation/generate-word', [ConsulationController::class, 'Generateword']);
        // Route::post('/api/consultation/generate-pdf', [ConsulationController::class, 'GenerateDocuments']);
        Route::put('/api/consulations/{id}', [ConsulationController::class, 'update']);
        Route::delete('/api/consulations/{id}', [ConsulationController::class, 'destroy']);
        Route::get('/api/consulations/search', [ConsulationController::class, 'search']); 

Route::get('/templates/content', [ConsulationController::class, 'getTemplateContent']);
Route::post('/consultation/convert-to-pdf', [ConsulationController::class, 'convertToPdf']);
        Route::apiResource('api/templates', TemplateController::class);
        Route::get('api/templates/search', [TemplateController::class, 'search']);

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

        Route::apiResource('folders', FolderController::class);
        Route::get('folders/search', [FolderController::class, 'search']);

Route::prefix('/api/consultation')->group(function () {
    Route::post('/generate-word', [ConsulationController::class, 'generateWord']);
    Route::post('/generate-pdf', [ConsulationController::class, 'generatePdf']);
    Route::post('/convert-to-pdf', [ConsulationController::class, 'convertToPdf']);
    Route::post('/{patientId}/save-pdf', [ConsulationController::class, 'savePdf']);
    Route::get('/{patientId}/documents', [ConsulationController::class, 'GetPatientConsultaionDoc']);

});

// Group all API routes under a prefix for better organization
Route::prefix('api')->group(function () {
    // Resource route for CRUD operations on prescriptions
    Route::resource('prescriptions', PrescriptionController::class);

    // Custom routes that don't fit the standard resource pattern
    // These should be placed *after* the resource definition to avoid conflicts
    Route::get('prescriptions/{id}/download-pdf', [PrescriptionController::class, 'downloadPrescriptionPdf']);
    Route::get('prescriptions/{id}/print', [PrescriptionController::class, 'printPrescription']);
});
// Patient Allergies Routes
Route::get('/api/patients/{patient}/Allergies', [PatientAllergiesController::class, 'index']);
Route::post('/api/patients/{patient}/Allergies/bulk', [PatientAllergiesController::class, 'bulkUpdate']);
Route::post('/api/patients/{patient}/Allergies', [PatientAllergiesController::class, 'store']);

// Catch-all route for views
Route::get('{view}', [ApplicationController::class, '__invoke'])
    ->where('view', '(.*)');

});
Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

