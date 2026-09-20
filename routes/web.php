<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\SuperAdminDashboardController;
use App\Http\Controllers\Dashboard\AdminDashboardController;
use App\Http\Controllers\Dashboard\DoctorDashboardController;
use App\Http\Controllers\Dashboard\NurseDashboardController;
use App\Http\Controllers\Dashboard\ReceptionDashboardController;
use App\Http\Controllers\Dashboard\LabDashboardController;
use App\Http\Controllers\Dashboard\PharmacistDashboardController;
use App\Http\Controllers\Dashboard\StaffDashboardController;
use App\Http\Controllers\Dashboard\PatientDashboardController;
use App\Http\Controllers\Patient\PatientController;
use App\Http\Controllers\Patient\EMRController;
use App\Http\Controllers\Doctor\DoctorController;
use App\Http\Controllers\Doctor\ConsultationController;
use App\Http\Controllers\Doctor\PrescriptionController;
use App\Http\Controllers\Appointment\AppointmentController;
use App\Http\Controllers\Lab\LabTestController;
use App\Http\Controllers\Lab\LabOrderController;
use App\Http\Controllers\Pharmacy\DrugController;
use App\Http\Controllers\Pharmacy\PharmacyController;
use App\Http\Controllers\Billing\BillingServiceController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\UserProfileController;
use App\Http\Controllers\Ward\WardController;
use App\Http\Controllers\FoodIntake\FoodIntakeController;
use App\Http\Controllers\FluidIntake\FluidIntakeController;
use App\Http\Controllers\StaffRounds\StaffRoundsController;
use App\Http\Controllers\Staff\StaffController;
use App\Http\Controllers\Referral\ReferralController;
use App\Http\Controllers\ConsentForm\ConsentFormController;
use App\Http\Controllers\Setting\SettingsController;
use App\Http\Controllers\Setting\ActivityLogController;
use App\Http\Controllers\Subscription\SubscriptionController;
use App\Http\Controllers\EMar\EMarController;
use App\Http\Controllers\Vitals\VitalsController;
use App\Http\Controllers\Nursing\NursingNoteController;
use App\Http\Controllers\ShiftHandover\ShiftHandoverController;
use App\Http\Controllers\Insurance\InsuranceClaimController;
use App\Http\Controllers\IPD\IpdDepositController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Radiology\RadiologyController;
use App\Http\Controllers\Billing\SchemeBillingController;
use App\Http\Controllers\OPD\OpdQueueController;
use App\Http\Controllers\Inventory\InventoryController;
use App\Http\Controllers\Inventory\PurchaseOrderController;
use App\Http\Controllers\ER\ErController;
use App\Http\Controllers\ICD\Icd10Controller;
use App\Http\Controllers\OT\OtController;
use App\Http\Controllers\ICU\IcuController;
use App\Http\Controllers\BloodBank\BloodBankController;
use App\Http\Controllers\HR\AttendanceController;
use App\Http\Controllers\HR\LeaveController;
use App\Http\Controllers\Portal\PatientPortalController;



Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        $user = auth()->user();

        $role = collect([
            'super_admin', 'admin', 'doctor', 'nurse',
            'receptionist', 'lab_assistant', 'pharmacist', 'staff', 'patient',
        ])->first(fn ($r) => $user->hasRole($r));

        return match ($role) {
            'super_admin'   => app(SuperAdminDashboardController::class)->index(),
            'admin'         => app(AdminDashboardController::class)->index(),
            'doctor'        => app(DoctorDashboardController::class)->index(),
            'nurse'         => app(NurseDashboardController::class)->index(),
            'receptionist'  => app(ReceptionDashboardController::class)->index(),
            'lab_assistant' => app(LabDashboardController::class)->index(),
            'pharmacist'    => app(PharmacistDashboardController::class)->index(),
            'staff'         => app(StaffDashboardController::class)->index(),
            'patient'       => app(PatientDashboardController::class)->index(),
            default         => abort(403, 'Your account has no role with dashboard access.'),
        };
    })->name('dashboard');

   
    Route::get('/dashboard/coming-soon', function (\Illuminate\Http\Request $request) {
        return view('dashboard.coming-soon', [
            'feature' => $request->query('feature', 'This feature'),
        ]);
    })->name('dashboard.placeholder');

    
    Route::get('/api/search', function (\Illuminate\Http\Request $request) {
        $query = $request->get('q');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = [];

       
        $nameHash = \App\Models\Patient::hashFor($query);
        $patients = \App\Models\Patient::where('mrn', 'LIKE', "%{$query}%")
            ->when($nameHash, fn ($q) => $q->orWhere('name_hash', $nameHash))
            ->limit(5)
            ->get();
        foreach ($patients as $patient) {
            $results[] = [
                'type' => 'patient',
                'title' => $patient->name,
                'subtitle' => "MRN: {$patient->mrn}",
                'url' => route('patients.show', $patient->id),
            ];
        }

        // Search Doctors
        $doctors = \App\Models\Doctor::where('name', 'LIKE', "%{$query}%")
            ->orWhere('specialization', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get();
        foreach ($doctors as $doctor) {
            $results[] = [
                'type' => 'doctor',
                'title' => "Dr. {$doctor->name}",
                'subtitle' => $doctor->specialization,
                'url' => route('doctors.show', $doctor->id),
                'photo_url' => $doctor->photo_url,
            ];
        }

        // Search Appointments
        $appointments = \App\Models\Appointment::whereHas('patient', function ($q) use ($query) {
            $q->where('name', 'LIKE', "%{$query}%");
        })->limit(5)->get();
        foreach ($appointments as $appointment) {
            $results[] = [
                'type' => 'appointment',
                'title' => "Appointment with {$appointment->patient->name}",
                'subtitle' => $appointment->appointment_date
                    ? \Carbon\Carbon::parse($appointment->appointment_date)->format('d M Y')
                    : '',
                'url' => route('appointments.show', $appointment->id),
            ];
        }

        return response()->json($results);
    })->name('api.search');
});


// Profile
Route::middleware(['auth'])->group(function () {
    Route::get('/user/profile', [UserProfileController::class, 'show'])->name('profile.show');
    Route::put('/user/profile', [UserProfileController::class, 'update'])->name('profile.update');
    Route::post('/user/profile/leave-request', [UserProfileController::class, 'requestLeave'])->name('profile.leave-request');
});

// Patients
Route::middleware(['auth', 'role:super_admin|admin|doctor|receptionist|nurse'])->group(function () {
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/patients/data', [PatientController::class, 'data'])->name('patients.data');
    Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
    Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
    Route::get('/patients/{patient}/json', [PatientController::class, 'getJson'])->name('patients.json');
    Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
    Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');
    Route::get('/patients/{patient}/print', [PatientController::class, 'print'])->name('patients.print');
});

// Patient medical records routes
Route::middleware(['auth', 'role:super_admin|admin|doctor|receptionist|nurse'])->group(function () {
    Route::post('/patients/{patient}/medical-records', [EMRController::class, 'storeMedicalRecord'])->name('patients.medical-records.store');
    Route::delete('/patients/{patient}/medical-records/{record}', [EMRController::class, 'destroyMedicalRecord'])->name('patients.medical-records.destroy');
    Route::post('/patients/{patient}/allergies', [EMRController::class, 'storeAllergy'])->name('patients.allergies.store');
    Route::delete('/patients/{patient}/allergies/{allergy}', [EMRController::class, 'destroyAllergy'])->name('patients.allergies.destroy');
    Route::post('/patients/{patient}/problems', [EMRController::class, 'storeProblem'])->name('patients.problems.store');
    Route::delete('/patients/{patient}/problems/{problem}', [EMRController::class, 'destroyProblem'])->name('patients.problems.destroy');
    Route::post('/patients/{patient}/attachments', [EMRController::class, 'storeAttachment'])->name('patients.attachments.store');
    Route::delete('/patients/{patient}/attachments/{attachment}', [EMRController::class, 'destroyAttachment'])->name('patients.attachments.destroy');
    Route::get('/patients/attachments/{attachment}/download', [EMRController::class, 'downloadAttachment'])->name('patients.attachments.download');
});

// Doctors
Route::middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::get('/doctors', [DoctorController::class, 'index'])->name('doctors.index');
    Route::get('/doctors/data', [DoctorController::class, 'data'])->name('doctors.data');
    Route::post('/doctors', [DoctorController::class, 'store'])->name('doctors.store');
    Route::get('/doctors/{doctor}', [DoctorController::class, 'show'])->name('doctors.show');
    Route::get('/doctors/{doctor}/download/{type}', [DoctorController::class, 'download'])->name('doctors.download');
    Route::get('/doctors/{doctor}/json', [DoctorController::class, 'getJson'])->name('doctors.json');
    Route::put('/doctors/{doctor}', [DoctorController::class, 'update'])->name('doctors.update');
    Route::delete('/doctors/{doctor}', [DoctorController::class, 'destroy'])->name('doctors.destroy');
    Route::post('/doctors/{doctor}/schedules', [DoctorController::class, 'addSchedule'])->name('doctors.schedules.store');
    Route::delete('/doctors/schedules/{schedule}', [DoctorController::class, 'deleteSchedule'])->name('doctors.schedules.destroy');
});

// Appointments
Route::middleware(['auth', 'role:super_admin|admin|doctor|receptionist|patient'])->group(function () {
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/data', [AppointmentController::class, 'data'])->name('appointments.data');
});

Route::middleware(['auth', 'role:super_admin|admin|doctor|receptionist'])->group(function () {
    Route::get('/appointments/calendar', [AppointmentController::class, 'calendar'])->name('appointments.calendar');
    Route::get('/appointments/calendar/data', [AppointmentController::class, 'calendarData'])->name('appointments.calendar.data');
    Route::get('/appointments/slots', [AppointmentController::class, 'getSlots'])->name('appointments.slots');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('/appointments/{appointment}/json', [AppointmentController::class, 'getJson'])->name('appointments.json');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');
    Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
});

// Consultations & Prescriptions
Route::middleware(['auth', 'role:super_admin|admin|doctor'])->group(function () {
    Route::get('/appointments/{appointment}/consultation', [ConsultationController::class, 'create'])->name('consultations.create');
    Route::post('/appointments/{appointment}/consultation', [ConsultationController::class, 'store'])->name('consultations.store');
    Route::get('/consultations/{consultation}/prescription/create', [PrescriptionController::class, 'create'])->name('prescriptions.create');
    Route::post('/consultations/{consultation}/prescription', [PrescriptionController::class, 'store'])->name('prescriptions.store');
});

Route::middleware(['auth', 'role:super_admin|admin|doctor|pharmacist|nurse|receptionist'])->group(function () {
    Route::get('/prescriptions/{prescription}', [PrescriptionController::class, 'show'])->name('prescriptions.show');
    Route::get('/prescriptions/{prescription}/print', [PrescriptionController::class, 'print'])->name('prescriptions.print');
});

// Lab
// Lab
Route::prefix('lab')->name('lab.')->middleware(['auth'])->group(function () {
    Route::middleware('role:super_admin|admin|lab_assistant|doctor|receptionist')->group(function () {
        Route::get('/tests', [LabTestController::class, 'index'])->name('tests.index');
        Route::post('/tests', [LabTestController::class, 'store'])->name('tests.store');
        Route::get('/tests/data', [LabTestController::class, 'data'])->name('tests.data');
        Route::put('/tests/{labTest}', [LabTestController::class, 'update'])->name('tests.update');
        Route::delete('/tests/{labTest}', [LabTestController::class, 'destroy'])->name('tests.destroy');
        Route::get('/tests/{labTest}/json', [LabTestController::class, 'getJson'])->name('tests.json');
        Route::get('/orders/create', [LabOrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [LabOrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{labOrder}/print', [LabOrderController::class, 'print'])->name('orders.print');
    });

    // Results entry is clinical data entry — receptionist excluded.
    Route::middleware('role:super_admin|admin|lab_assistant|doctor')->group(function () {
        Route::post('/orders/{labOrder}/results', [LabOrderController::class, 'enterResults'])->name('orders.results');
    });

 
    Route::middleware('role:super_admin|admin|lab_assistant|doctor|receptionist|patient')->group(function () {
        Route::get('/orders', [LabOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/data', [LabOrderController::class, 'data'])->name('orders.data');
        Route::get('/orders/{labOrder}', [LabOrderController::class, 'show'])->name('orders.show');
    });
});

// Pharmacy
Route::prefix('pharmacy')->name('pharmacy.')->middleware(['auth', 'role:super_admin|admin|pharmacist|receptionist'])->group(function () {
    Route::get('/drugs', [DrugController::class, 'index'])->name('drugs.index');
    Route::get('/drugs/data', [DrugController::class, 'data'])->name('drugs.data');
    Route::post('/drugs', [DrugController::class, 'store'])->name('drugs.store');
    Route::get('/drugs/{drug}/json', [DrugController::class, 'getJson'])->name('drugs.json');
    Route::put('/drugs/{drug}', [DrugController::class, 'update'])->name('drugs.update');
    Route::delete('/drugs/{drug}', [DrugController::class, 'destroy'])->name('drugs.destroy');
    Route::post('/drugs/{drug}/stock', [DrugController::class, 'addStock'])->name('drugs.stock');
    Route::get('/drugs/{drug}/stock-history', [DrugController::class, 'stockHistory'])->name('drugs.stock-history');

    Route::get('/dispensing', [PharmacyController::class, 'index'])->name('dispensing.index');
    Route::get('/dispensing/data', [PharmacyController::class, 'data'])->name('dispensing.data');
});


Route::prefix('pharmacy')->name('pharmacy.')->middleware(['auth', 'role:super_admin|admin|pharmacist'])->group(function () {
    Route::get('/dispensing/create', [PharmacyController::class, 'create'])->name('dispensing.create');
    Route::post('/dispensing', [PharmacyController::class, 'store'])->name('dispensing.store');
});

Route::prefix('pharmacy')->name('pharmacy.')->middleware(['auth', 'role:super_admin|admin|pharmacist|receptionist'])->group(function () {
    Route::get('/dispensing/from-prescription/{prescription}', [PharmacyController::class, 'fromPrescription'])->name('dispensing.from-prescription');
    Route::get('/dispensing/{dispensing}', [PharmacyController::class, 'show'])->name('dispensing.show');
    Route::get('/alerts', [PharmacyController::class, 'alerts'])->name('alerts');
});

// Billing
Route::prefix('billing')->name('billing.')->middleware(['auth', 'role:super_admin|admin|receptionist'])->group(function () {
    Route::get('/services', [BillingServiceController::class, 'index'])->name('services.index');
    Route::get('/services/data', [BillingServiceController::class, 'data'])->name('services.data');
    Route::post('/services', [BillingServiceController::class, 'store'])->name('services.store');
    Route::get('/services/{billingService}/json', [BillingServiceController::class, 'getJson'])->name('services.json');
    Route::put('/services/{billingService}', [BillingServiceController::class, 'update'])->name('services.update');
    Route::delete('/services/{billingService}', [BillingServiceController::class, 'destroy'])->name('services.destroy');
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/data', [InvoiceController::class, 'data'])->name('invoices.data');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::post('/invoices/{invoice}/payment', [InvoiceController::class, 'addPayment'])->name('invoices.payment');

    Route::get('/schemes', [SchemeBillingController::class, 'index'])->name('schemes.index');
    Route::get('/schemes/data', [SchemeBillingController::class, 'data'])->name('schemes.data');
    Route::post('/schemes', [SchemeBillingController::class, 'store'])->name('schemes.store');
    Route::get('/schemes/{scheme}', [SchemeBillingController::class, 'show'])->name('schemes.show');
    Route::put('/schemes/{scheme}', [SchemeBillingController::class, 'update'])->name('schemes.update');
    Route::put('/schemes/{scheme}/status', [SchemeBillingController::class, 'updateStatus'])->name('schemes.status');
    Route::delete('/schemes/{scheme}', [SchemeBillingController::class, 'destroy'])->name('schemes.destroy');
});

// Reports
Route::prefix('reports')->name('reports.')->middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('/patients', [ReportController::class, 'patients'])->name('patients');
    Route::get('/patients/pdf', [ReportController::class, 'patientsPdf'])->name('patients.pdf');
    Route::get('/billing', [ReportController::class, 'billing'])->name('billing');
    Route::get('/billing/pdf', [ReportController::class, 'billingPdf'])->name('billing.pdf');
    Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
    Route::get('/inventory/pdf', [ReportController::class, 'inventoryPdf'])->name('inventory.pdf');
    Route::get('/kpis', [ReportController::class, 'kpis'])->name('kpis');
});

// Settings


Route::prefix('settings')->name('settings.')->middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('index');
    Route::post('/', [SettingsController::class, 'update'])->name('update');
    Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log');
    Route::get('/activity-log/data', [ActivityLogController::class, 'data'])->name('activity-log.data');
    Route::post('/activity-log/clear', [ActivityLogController::class, 'clear'])->name('activity-log.clear');
    Route::get('/departments', [SettingsController::class, 'departments'])->name('departments');
    Route::post('/departments', [SettingsController::class, 'storeDepartment'])->name('departments.store');
    Route::put('/departments/{department}', [SettingsController::class, 'updateDepartment'])->name('departments.update');
    Route::delete('/departments/{department}', [SettingsController::class, 'destroyDepartment'])->name('departments.destroy');
});

// User Management
Route::middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/data', [UserController::class, 'data'])->name('users.data');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/json', [UserController::class, 'getJson'])->name('users.json');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::put('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});

// Staff Management
Route::middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::get('/staff/data', [StaffController::class, 'data'])->name('staff.data');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    Route::get('/staff/{user}', [StaffController::class, 'show'])->name('staff.show');
    Route::get('/staff/{user}/json', [StaffController::class, 'getJson'])->name('staff.json');
    Route::put('/staff/{user}', [StaffController::class, 'update'])->name('staff.update');
    Route::delete('/staff/{user}', [StaffController::class, 'destroy'])->name('staff.destroy');
});

// Ward
Route::middleware(['auth', 'role:super_admin|admin|nurse|receptionist|doctor'])->group(function () {
    Route::get('/ward', [WardController::class, 'index'])->name('ward.index');
    Route::get('/ward/data', [WardController::class, 'data'])->name('ward.data');
    Route::post('/ward', [WardController::class, 'store'])->name('ward.store');
    Route::get('/ward/admissions', [WardController::class, 'admissions'])->name('ward.admissions');
    Route::get('/ward/admissions/data', [WardController::class, 'admissionsData'])->name('ward.admissions.data');
});

// Ward admit — doctors are read-only on wards/admissions, so excluded here
Route::middleware(['auth', 'role:super_admin|admin|nurse|receptionist'])->group(function () {
    Route::post('/ward/admit', [WardController::class, 'admit'])->name('ward.admit');
});

// Ward discharge/transfer — doctors are read-only on wards/admissions, so excluded here
Route::middleware(['auth', 'role:super_admin|admin|nurse'])->group(function () {
    Route::put('/ward/discharge/{admission}', [WardController::class, 'discharge'])->name('ward.discharge');
    Route::put('/ward/transfer/{admission}', [WardController::class, 'transfer'])->name('ward.transfer');
    Route::get('/ward/admissions/{admission}/balance', [WardController::class, 'balanceInfo'])->name('ward.admissions.balance');
});

Route::middleware(['auth', 'role:super_admin|admin|nurse|receptionist|doctor'])->group(function () {
    Route::get('/ward/available-beds', [WardController::class, 'availableBeds'])->name('ward.available-beds');
    Route::get('/ward/{ward}', [WardController::class, 'show'])->name('ward.show');
    Route::put('/ward/{ward}', [WardController::class, 'update'])->name('ward.update');
    Route::delete('/ward/{ward}', [WardController::class, 'destroy'])->name('ward.destroy');
});

// Food Intake
Route::middleware(['auth', 'role:super_admin|admin|nurse|staff'])->group(function () {
    Route::get('/food-intake', [FoodIntakeController::class, 'index'])->name('food-intake.index');
    Route::get('/food-intake/data', [FoodIntakeController::class, 'data'])->name('food-intake.data');
    Route::get('/food-intake/{patient}', [FoodIntakeController::class, 'show'])->name('food-intake.show');
    Route::post('/food-intake/{patient}/diet-plan', [FoodIntakeController::class, 'storeDietPlan'])->name('food-intake.diet-plan.store');
    Route::post('/food-intake/{patient}/log-meal', [FoodIntakeController::class, 'logMeal'])->name('food-intake.log-meal');
});

// Fluid Intake
Route::middleware(['auth', 'role:super_admin|admin|nurse|staff'])->group(function () {
    Route::get('/fluid-intake', [FluidIntakeController::class, 'index'])->name('fluid-intake.index');
    Route::get('/fluid-intake/data', [FluidIntakeController::class, 'data'])->name('fluid-intake.data');
    Route::get('/fluid-intake/{patient}', [FluidIntakeController::class, 'show'])->name('fluid-intake.show');
    Route::post('/fluid-intake/{patient}/log', [FluidIntakeController::class, 'logFluid'])->name('fluid-intake.log');
    Route::delete('/fluid-logs/{fluidLog}', [FluidIntakeController::class, 'destroy'])->name('fluid-intake.destroy');
});

// Staff Rounds
Route::middleware(['auth', 'role:super_admin|admin|nurse'])->group(function () {
    Route::get('/staff-rounds', [StaffRoundsController::class, 'index'])->name('staff-rounds.index');
    Route::get('/staff-rounds/assignments/data', [StaffRoundsController::class, 'assignmentsData'])->name('staff-rounds.assignments.data');
    Route::post('/staff-rounds/assign', [StaffRoundsController::class, 'assign'])->name('staff-rounds.assign');
    Route::put('/staff-rounds/deactivate/{assignment}', [StaffRoundsController::class, 'deactivate'])->name('staff-rounds.deactivate');
});

Route::middleware(['auth', 'role:super_admin|admin|nurse|staff'])->group(function () {
    Route::get('/staff-rounds/my-rounds', [StaffRoundsController::class, 'myRounds'])->name('staff-rounds.my-rounds');
    Route::get('/staff-rounds/my-rounds/data', [StaffRoundsController::class, 'myRoundsData'])->name('staff-rounds.my-rounds.data');
    Route::get('/staff-rounds/alert-count', [StaffRoundsController::class, 'alertCount'])->name('staff-rounds.alert-count');
    Route::post('/staff-rounds/{patient}/mark', [StaffRoundsController::class, 'markRound'])->name('staff-rounds.mark');
    Route::get('/staff-rounds/{patient}/history', [StaffRoundsController::class, 'history'])->name('staff-rounds.history');
});

// Referrals
Route::middleware(['auth', 'role:super_admin|admin|doctor|receptionist'])->group(function () {
    Route::get('/referrals', [ReferralController::class, 'index'])->name('referrals.index');
    Route::get('/referrals/data', [ReferralController::class, 'data'])->name('referrals.data');
    Route::post('/referrals', [ReferralController::class, 'store'])->name('referrals.store');
    Route::put('/referrals/{referral}/status', [ReferralController::class, 'updateStatus'])->name('referrals.status');
    Route::delete('/referrals/{referral}', [ReferralController::class, 'destroy'])->name('referrals.destroy');
});

// Consent Forms
Route::middleware(['auth', 'role:super_admin|admin|doctor|receptionist|nurse'])->group(function () {
    Route::get('/consent-forms', [ConsentFormController::class, 'index'])->name('consent-forms.index');
    Route::get('/consent-forms/data', [ConsentFormController::class, 'data'])->name('consent-forms.data');
    Route::post('/consent-forms', [ConsentFormController::class, 'store'])->name('consent-forms.store');
    Route::get('/consent-forms/{consentForm}/sign', [ConsentFormController::class, 'sign'])->name('consent-forms.sign');
    Route::post('/consent-forms/{consentForm}/signature', [ConsentFormController::class, 'saveSignature'])->name('consent-forms.signature');
    Route::put('/consent-forms/{consentForm}/status', [ConsentFormController::class, 'updateStatus'])->name('consent-forms.status');
    Route::get('/consent-forms/{consentForm}/print', [ConsentFormController::class, 'print'])->name('consent-forms.print');
    Route::delete('/consent-forms/{consentForm}', [ConsentFormController::class, 'destroy'])->name('consent-forms.destroy');
});

// eMAR - Medication Administration Record
Route::middleware(['auth', 'role:super_admin|admin|doctor|nurse'])->group(function () {
    Route::get('/emar', [EMarController::class, 'index'])->name('emar.index');
    Route::get('/emar/data', [EMarController::class, 'data'])->name('emar.data');
    Route::post('/emar', [EMarController::class, 'store'])->name('emar.store');
    Route::put('/emar/{mar}/administer', [EMarController::class, 'administer'])->name('emar.administer');
    Route::put('/emar/{mar}', [EMarController::class, 'update'])->name('emar.update');
    Route::delete('/emar/{mar}', [EMarController::class, 'destroy'])->name('emar.destroy');
});

// Vitals / Observations
Route::middleware(['auth', 'role:super_admin|admin|doctor|nurse'])->group(function () {
    Route::get('/vitals', [VitalsController::class, 'index'])->name('vitals.index');
    Route::get('/vitals/data', [VitalsController::class, 'data'])->name('vitals.data');
    Route::post('/vitals', [VitalsController::class, 'store'])->name('vitals.store');
    Route::get('/vitals/{patient}', [VitalsController::class, 'show'])->name('vitals.show');
    Route::delete('/vitals/{vital}', [VitalsController::class, 'destroy'])->name('vitals.destroy');
});

// Nursing / Progress Notes
Route::middleware(['auth', 'role:super_admin|admin|doctor|nurse'])->group(function () {
    Route::get('/nursing', [NursingNoteController::class, 'index'])->name('nursing.index');
    Route::get('/nursing/data', [NursingNoteController::class, 'data'])->name('nursing.data');
    Route::post('/nursing', [NursingNoteController::class, 'store'])->name('nursing.store');
    Route::get('/nursing/{note}', [NursingNoteController::class, 'show'])->name('nursing.show');
    Route::put('/nursing/{note}', [NursingNoteController::class, 'update'])->name('nursing.update');
    Route::delete('/nursing/{note}', [NursingNoteController::class, 'destroy'])->name('nursing.destroy');
});

// Shift Handover
Route::middleware(['auth', 'role:super_admin|admin|nurse'])->prefix('shift-handover')->name('shift-handover.')->group(function () {
    Route::get('/', [ShiftHandoverController::class, 'index'])->name('index');
    Route::get('/data', [ShiftHandoverController::class, 'data'])->name('data');
    Route::get('/ward-patients', [ShiftHandoverController::class, 'wardPatients'])->name('ward-patients');
    Route::get('/create', [ShiftHandoverController::class, 'create'])->name('create');
    Route::post('/', [ShiftHandoverController::class, 'store'])->name('store');
    Route::get('/alert-count', [ShiftHandoverController::class, 'alertCount'])->name('alert-count');
    Route::get('/reports', [ShiftHandoverController::class, 'report'])->name('report');
    Route::get('/{shiftHandover}', [ShiftHandoverController::class, 'show'])->name('show');
    Route::get('/{shiftHandover}/edit', [ShiftHandoverController::class, 'edit'])->name('edit');
    Route::put('/{shiftHandover}', [ShiftHandoverController::class, 'update'])->name('update');
    Route::post('/{shiftHandover}/submit', [ShiftHandoverController::class, 'submit'])->name('submit');
    Route::post('/{shiftHandover}/accept', [ShiftHandoverController::class, 'accept'])->name('accept');
    Route::post('/{shiftHandover}/reject', [ShiftHandoverController::class, 'reject'])->name('reject');
    Route::get('/{shiftHandover}/print', [ShiftHandoverController::class, 'print'])->name('print');
    Route::delete('/{shiftHandover}', [ShiftHandoverController::class, 'destroy'])->name('destroy');
});

// Insurance / TPA / Cashless Claims
Route::middleware(['auth', 'role:super_admin|admin|receptionist'])->group(function () {
    Route::get('/insurance', [InsuranceClaimController::class, 'index'])->name('insurance.index');
    Route::get('/insurance/data', [InsuranceClaimController::class, 'data'])->name('insurance.data');
    Route::post('/insurance', [InsuranceClaimController::class, 'store'])->name('insurance.store');
    Route::get('/insurance/{claim}', [InsuranceClaimController::class, 'show'])->name('insurance.show');
    Route::put('/insurance/{claim}', [InsuranceClaimController::class, 'update'])->name('insurance.update');
    Route::put('/insurance/{claim}/status', [InsuranceClaimController::class, 'updateStatus'])->name('insurance.status');
    Route::delete('/insurance/{claim}', [InsuranceClaimController::class, 'destroy'])->name('insurance.destroy');
});

// IPD Advance / Deposit Management
Route::middleware(['auth', 'role:super_admin|admin|receptionist'])->group(function () {
    Route::get('/ipd/deposits', [IpdDepositController::class, 'index'])->name('ipd.deposits.index');
    Route::get('/ipd/deposits/data', [IpdDepositController::class, 'data'])->name('ipd.deposits.data');
    Route::post('/ipd/deposits', [IpdDepositController::class, 'store'])->name('ipd.deposits.store');
    Route::get('/ipd/deposits/{deposit}', [IpdDepositController::class, 'show'])->name('ipd.deposits.show');
    Route::post('/ipd/deposits/{deposit}/refund', [IpdDepositController::class, 'refund'])->name('ipd.deposits.refund');
    Route::delete('/ipd/deposits/{deposit}', [IpdDepositController::class, 'destroy'])->name('ipd.deposits.destroy');
});

// SMS / WhatsApp / Email Notifications
Route::middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/data', [NotificationController::class, 'data'])->name('notifications.data');
    Route::post('/notifications/send', [NotificationController::class, 'send'])->name('notifications.send');
    Route::get('/notifications/templates', [NotificationController::class, 'templates'])->name('notifications.templates');
    Route::post('/notifications/templates', [NotificationController::class, 'storeTemplate'])->name('notifications.templates.store');
    Route::put('/notifications/templates/{template}', [NotificationController::class, 'updateTemplate'])->name('notifications.templates.update');
});

// Radiology / Imaging (RIS)
Route::middleware(['auth', 'role:super_admin|admin|doctor|receptionist'])->group(function () {
    Route::get('/radiology', [RadiologyController::class, 'index'])->name('radiology.index');
    Route::get('/radiology/data', [RadiologyController::class, 'data'])->name('radiology.data');
    Route::post('/radiology', [RadiologyController::class, 'store'])->name('radiology.store');
    Route::get('/radiology/{order}', [RadiologyController::class, 'show'])->name('radiology.show');
    Route::put('/radiology/{order}', [RadiologyController::class, 'update'])->name('radiology.update');
    Route::put('/radiology/{order}/findings', [RadiologyController::class, 'enterFindings'])->name('radiology.findings');
    Route::delete('/radiology/{order}', [RadiologyController::class, 'destroy'])->name('radiology.destroy');
});

// OPD Queue / Token Management
Route::middleware(['auth', 'role:super_admin|admin|receptionist|doctor'])->group(function () {
    Route::get('/opd/queue', [OpdQueueController::class, 'index'])->name('opd.queue.index');
    Route::get('/opd/queue/data', [OpdQueueController::class, 'data'])->name('opd.queue.data');
    Route::get('/opd/queue/stats', [OpdQueueController::class, 'stats'])->name('opd.queue.stats');
    Route::post('/opd/queue', [OpdQueueController::class, 'store'])->name('opd.queue.store');
    Route::put('/opd/queue/call-next/{doctor}', [OpdQueueController::class, 'callNext'])->name('opd.queue.call-next');
    Route::put('/opd/queue/{token}/status', [OpdQueueController::class, 'updateStatus'])->name('opd.queue.status');
    Route::delete('/opd/queue/{token}', [OpdQueueController::class, 'destroy'])->name('opd.queue.destroy');
});
Route::get('/opd/display', [OpdQueueController::class, 'display'])->name('opd.display');

// General Inventory & Procurement
Route::middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/data', [InventoryController::class, 'data'])->name('inventory.data');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/{item}/json', [InventoryController::class, 'getJson'])->name('inventory.json');
    Route::put('/inventory/{item}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/{item}', [InventoryController::class, 'destroy'])->name('inventory.destroy');

    Route::get('/inventory/purchase-orders', [PurchaseOrderController::class, 'index'])->name('inventory.po.index');
    Route::get('/inventory/purchase-orders/data', [PurchaseOrderController::class, 'data'])->name('inventory.po.data');
    Route::post('/inventory/purchase-orders', [PurchaseOrderController::class, 'store'])->name('inventory.po.store');
    Route::put('/inventory/purchase-orders/{po}/receive', [PurchaseOrderController::class, 'receive'])->name('inventory.po.receive');
    Route::delete('/inventory/purchase-orders/{po}', [PurchaseOrderController::class, 'destroy'])->name('inventory.po.destroy');
});

// Emergency / Casualty (ER)
Route::middleware(['auth', 'role:super_admin|admin|doctor|nurse|receptionist'])->group(function () {
    Route::get('/er', [ErController::class, 'index'])->name('er.index');
    Route::get('/er/data', [ErController::class, 'data'])->name('er.data');
    Route::post('/er', [ErController::class, 'store'])->name('er.store');
    Route::get('/er/{er}', [ErController::class, 'show'])->name('er.show');
    Route::put('/er/{er}', [ErController::class, 'update'])->name('er.update');
    Route::put('/er/{er}/triage', [ErController::class, 'triage'])->name('er.triage');
    Route::put('/er/{er}/discharge', [ErController::class, 'discharge'])->name('er.discharge');
    Route::delete('/er/{er}', [ErController::class, 'destroy'])->name('er.destroy');
});

// ICD-10 Diagnosis Coding
Route::middleware(['auth'])->group(function () {
    Route::get('/icd10', [Icd10Controller::class, 'index'])->name('icd10.index');
    Route::get('/icd10/data', [Icd10Controller::class, 'data'])->name('icd10.data');
    Route::get('/icd10/search', [Icd10Controller::class, 'search'])->name('icd10.search');
});
Route::middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::post('/icd10', [Icd10Controller::class, 'store'])->name('icd10.store');
    Route::put('/icd10/{code}', [Icd10Controller::class, 'update'])->name('icd10.update');
    Route::delete('/icd10/{code}', [Icd10Controller::class, 'destroy'])->name('icd10.destroy');
});

// Operation Theatre (OT) Management
Route::middleware(['auth', 'role:super_admin|admin|doctor|nurse'])->group(function () {
    Route::get('/ot', [OtController::class, 'index'])->name('ot.index');
    Route::get('/ot/data', [OtController::class, 'data'])->name('ot.data');
    Route::post('/ot', [OtController::class, 'store'])->name('ot.store');
    Route::get('/ot/{ot}', [OtController::class, 'show'])->name('ot.show');
    Route::put('/ot/{ot}', [OtController::class, 'update'])->name('ot.update');
    Route::put('/ot/{ot}/start', [OtController::class, 'start'])->name('ot.start');
    Route::put('/ot/{ot}/complete', [OtController::class, 'complete'])->name('ot.complete');
    Route::delete('/ot/{ot}', [OtController::class, 'destroy'])->name('ot.destroy');
});

// ICU Charting
Route::middleware(['auth', 'role:super_admin|admin|doctor|nurse'])->group(function () {
    Route::get('/icu', [IcuController::class, 'index'])->name('icu.index');
    Route::get('/icu/data', [IcuController::class, 'data'])->name('icu.data');
    Route::post('/icu', [IcuController::class, 'store'])->name('icu.store');
    Route::get('/icu/{chart}', [IcuController::class, 'show'])->name('icu.show');
    Route::delete('/icu/{chart}', [IcuController::class, 'destroy'])->name('icu.destroy');
});

// Blood Bank
Route::middleware(['auth', 'role:super_admin|admin|nurse|doctor'])->group(function () {
    Route::get('/blood-bank', [BloodBankController::class, 'index'])->name('blood-bank.index');
    Route::get('/blood-bank/data', [BloodBankController::class, 'data'])->name('blood-bank.data');
    Route::get('/blood-bank/transfusions/data', [BloodBankController::class, 'transfusionsData'])->name('blood-bank.transfusions.data');
    Route::post('/blood-bank', [BloodBankController::class, 'store'])->name('blood-bank.store');
    Route::put('/blood-bank/{unit}/transfuse', [BloodBankController::class, 'transfuse'])->name('blood-bank.transfuse');
    Route::put('/blood-bank/{unit}/status', [BloodBankController::class, 'updateStatus'])->name('blood-bank.status');
    Route::delete('/blood-bank/{unit}', [BloodBankController::class, 'destroy'])->name('blood-bank.destroy');
});

// HR — Attendance & Roster
Route::middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::get('/hr/attendance', [AttendanceController::class, 'index'])->name('hr.attendance.index');
    Route::get('/hr/attendance/data', [AttendanceController::class, 'data'])->name('hr.attendance.data');
    Route::post('/hr/attendance', [AttendanceController::class, 'store'])->name('hr.attendance.store');
    Route::put('/hr/attendance/{attendance}', [AttendanceController::class, 'update'])->name('hr.attendance.update');

    Route::get('/hr/leaves', [LeaveController::class, 'index'])->name('hr.leaves.index');
    Route::get('/hr/leaves/data', [LeaveController::class, 'data'])->name('hr.leaves.data');
    Route::post('/hr/leaves', [LeaveController::class, 'store'])->name('hr.leaves.store');
    Route::put('/hr/leaves/{leave}/approve', [LeaveController::class, 'approve'])->name('hr.leaves.approve');
    Route::put('/hr/leaves/{leave}/reject', [LeaveController::class, 'reject'])->name('hr.leaves.reject');
    Route::delete('/hr/leaves/{leave}', [LeaveController::class, 'destroy'])->name('hr.leaves.destroy');
});

// Patient Portal Enhancement
Route::middleware(['auth', 'role:patient'])->group(function () {
    Route::get('/portal/book-appointment', [PatientPortalController::class, 'bookAppointment'])->name('portal.book-appointment');
    Route::post('/portal/book-appointment', [PatientPortalController::class, 'storeAppointment'])->name('portal.store-appointment');
    Route::get('/portal/slots', [PatientPortalController::class, 'slots'])->name('portal.slots');
    Route::get('/portal/prescriptions', [PatientPortalController::class, 'prescriptions'])->name('portal.prescriptions');
    Route::get('/portal/invoices', [PatientPortalController::class, 'invoices'])->name('portal.invoices');
    Route::post('/portal/invoices/{invoice}/request-payment', [PatientPortalController::class, 'requestPayment'])->name('portal.invoices.request-payment');
    Route::get('/portal/lab-results', [PatientPortalController::class, 'labResults'])->name('portal.lab-results');
    Route::get('/portal/lab-results/{order}/download', [PatientPortalController::class, 'downloadLabResult'])->name('portal.lab-results.download');
});


Route::middleware(['auth', 'role:super_admin'])->prefix('subscription')->name('subscription.')->group(function () {
   
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Subscription\PlanController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Subscription\PlanController::class, 'store'])->name('store');
        Route::get('/{plan}/json', [\App\Http\Controllers\Subscription\PlanController::class, 'getJson'])->name('json');
        Route::put('/{plan}', [\App\Http\Controllers\Subscription\PlanController::class, 'update'])->name('update');
        Route::patch('/{plan}/status', [\App\Http\Controllers\Subscription\PlanController::class, 'setStatus'])->name('set-status');
        Route::delete('/{plan}', [\App\Http\Controllers\Subscription\PlanController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/restore', [\App\Http\Controllers\Subscription\PlanController::class, 'restore'])->name('restore');
    });

    Route::get('/', [SubscriptionController::class, 'index'])->name('index');
    Route::get('/data', [SubscriptionController::class, 'data'])->name('data');
    Route::post('/', [SubscriptionController::class, 'store'])->name('store');

    
    Route::get('/{subscription}', [SubscriptionController::class, 'show'])->name('show')->where('subscription', '[0-9]+')->withTrashed();
    Route::get('/{subscription}/json', [SubscriptionController::class, 'getJson'])->name('json')->where('subscription', '[0-9]+');
    Route::put('/{subscription}', [SubscriptionController::class, 'update'])->name('update');
    Route::put('/{subscription}/extend', [SubscriptionController::class, 'extend'])->name('extend');
    Route::patch('/{subscription}/change-plan', [SubscriptionController::class, 'changePlan'])->name('change-plan');
    Route::post('/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
    Route::post('/{subscription}/reactivate', [SubscriptionController::class, 'reactivate'])->name('reactivate');
    Route::delete('/{subscription}', [SubscriptionController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/restore', [SubscriptionController::class, 'restore'])->name('restore');
});


Route::middleware(['auth', 'role:super_admin'])->prefix('super-admin/hospitals')->name('super-admin.hospitals.')->group(function () {
    Route::get('/', [\App\Http\Controllers\SuperAdmin\HospitalController::class, 'index'])->name('index');
    Route::get('/data', [\App\Http\Controllers\SuperAdmin\HospitalController::class, 'data'])->name('data');
    Route::post('/', [\App\Http\Controllers\SuperAdmin\HospitalController::class, 'store'])->name('store');
    Route::get('/{hospital}', [\App\Http\Controllers\SuperAdmin\HospitalController::class, 'show'])->name('show')->withTrashed();
    Route::get('/{hospital}/json', [\App\Http\Controllers\SuperAdmin\HospitalController::class, 'getJson'])->name('json');
    Route::put('/{hospital}', [\App\Http\Controllers\SuperAdmin\HospitalController::class, 'update'])->name('update');
    Route::patch('/{hospital}/status', [\App\Http\Controllers\SuperAdmin\HospitalController::class, 'setStatus'])->name('set-status');
    Route::delete('/{hospital}', [\App\Http\Controllers\SuperAdmin\HospitalController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/restore', [\App\Http\Controllers\SuperAdmin\HospitalController::class, 'restore'])->name('restore');

   
    Route::post('/{hospital}/admin', [\App\Http\Controllers\SuperAdmin\ImpersonationController::class, 'createAdmin'])->name('create-admin');
    Route::post('/{hospital}/impersonate', [\App\Http\Controllers\SuperAdmin\ImpersonationController::class, 'start'])->name('impersonate');
});


Route::middleware(['auth', 'role:super_admin'])->get('/super-admin/revenue', [\App\Http\Controllers\SuperAdmin\HospitalController::class, 'revenue'])->name('super-admin.revenue');


Route::middleware(['auth', 'role:super_admin'])->prefix('super-admin/analytics')->name('super-admin.analytics.')->group(function () {
    Route::get('/', [\App\Http\Controllers\SuperAdmin\AnalyticsController::class, 'index'])->name('index');
    Route::get('/export/csv', [\App\Http\Controllers\SuperAdmin\AnalyticsController::class, 'exportCsv'])->name('export-csv');
    Route::get('/export/pdf', [\App\Http\Controllers\SuperAdmin\AnalyticsController::class, 'pdf'])->name('pdf');
});
Route::middleware(['auth', 'role:super_admin'])->get('/super-admin/system-health', [\App\Http\Controllers\SuperAdmin\SystemHealthController::class, 'index'])->name('super-admin.health.index');

// Support Centre 
Route::middleware(['auth', 'role:super_admin'])->prefix('super-admin/support')->name('super-admin.support.')->group(function () {
    Route::get('/', [\App\Http\Controllers\SuperAdmin\SupportTicketController::class, 'index'])->name('index');
    Route::get('/data', [\App\Http\Controllers\SuperAdmin\SupportTicketController::class, 'data'])->name('data');
    Route::post('/', [\App\Http\Controllers\SuperAdmin\SupportTicketController::class, 'store'])->name('store');
    Route::get('/attachments/{attachment}/download', [\App\Http\Controllers\SuperAdmin\SupportTicketController::class, 'downloadAttachment'])->name('attachments.download');
    Route::get('/{ticket}', [\App\Http\Controllers\SuperAdmin\SupportTicketController::class, 'show'])->name('show')->where('ticket', '[0-9]+')->withTrashed();
    Route::get('/{ticket}/json', [\App\Http\Controllers\SuperAdmin\SupportTicketController::class, 'getJson'])->name('json')->where('ticket', '[0-9]+');
    Route::put('/{ticket}', [\App\Http\Controllers\SuperAdmin\SupportTicketController::class, 'update'])->name('update');
    Route::post('/{ticket}/assign', [\App\Http\Controllers\SuperAdmin\SupportTicketController::class, 'assign'])->name('assign');
    Route::patch('/{ticket}/status', [\App\Http\Controllers\SuperAdmin\SupportTicketController::class, 'setStatus'])->name('set-status');
    Route::patch('/{ticket}/priority', [\App\Http\Controllers\SuperAdmin\SupportTicketController::class, 'setPriority'])->name('set-priority');
    Route::post('/{ticket}/close', [\App\Http\Controllers\SuperAdmin\SupportTicketController::class, 'close'])->name('close');
    Route::post('/{ticket}/reopen', [\App\Http\Controllers\SuperAdmin\SupportTicketController::class, 'reopen'])->name('reopen');
    Route::post('/{ticket}/notes', [\App\Http\Controllers\SuperAdmin\SupportTicketController::class, 'addNote'])->name('notes.store');
    Route::post('/{ticket}/replies', [\App\Http\Controllers\SuperAdmin\SupportTicketController::class, 'addReply'])->name('replies.store');
    Route::post('/{ticket}/attachments', [\App\Http\Controllers\SuperAdmin\SupportTicketController::class, 'uploadAttachment'])->name('attachments.store');
    Route::delete('/{ticket}', [\App\Http\Controllers\SuperAdmin\SupportTicketController::class, 'archive'])->name('archive');
    Route::post('/{id}/restore', [\App\Http\Controllers\SuperAdmin\SupportTicketController::class, 'restore'])->name('restore');
});

// Reports & Export Centre 
Route::middleware(['auth', 'role:super_admin'])->prefix('super-admin/reports')->name('super-admin.reports.')->group(function () {
    Route::get('/', [\App\Http\Controllers\SuperAdmin\ReportCentreController::class, 'index'])->name('index');
    Route::get('/{slug}', [\App\Http\Controllers\SuperAdmin\ReportCentreController::class, 'show'])->name('show')->where('slug', '[a-z0-9-]+');
    Route::get('/{slug}/data', [\App\Http\Controllers\SuperAdmin\ReportCentreController::class, 'data'])->name('data')->where('slug', '[a-z0-9-]+');
    Route::get('/{slug}/export/csv', [\App\Http\Controllers\SuperAdmin\ReportCentreController::class, 'exportCsv'])->name('export-csv')->where('slug', '[a-z0-9-]+');
    Route::get('/{slug}/print', [\App\Http\Controllers\SuperAdmin\ReportCentreController::class, 'print'])->name('print')->where('slug', '[a-z0-9-]+');
});


Route::middleware(['auth', 'role:super_admin'])->prefix('super-admin/settings')->name('super-admin.settings.')->group(function () {
    Route::get('/', [\App\Http\Controllers\SuperAdmin\GlobalSettingsController::class, 'index'])->name('index');
    Route::put('/platform', [\App\Http\Controllers\SuperAdmin\GlobalSettingsController::class, 'updatePlatform'])->name('platform');
    Route::put('/regional', [\App\Http\Controllers\SuperAdmin\GlobalSettingsController::class, 'updateRegional'])->name('regional');
    Route::put('/security', [\App\Http\Controllers\SuperAdmin\GlobalSettingsController::class, 'updateSecurity'])->name('security');
    Route::put('/email', [\App\Http\Controllers\SuperAdmin\GlobalSettingsController::class, 'updateEmail'])->name('email');
    Route::post('/email/test', [\App\Http\Controllers\SuperAdmin\GlobalSettingsController::class, 'sendTestEmail'])->name('email.test');
    Route::put('/features', [\App\Http\Controllers\SuperAdmin\GlobalSettingsController::class, 'updateFeatures'])->name('features');
    Route::post('/reset', [\App\Http\Controllers\SuperAdmin\GlobalSettingsController::class, 'resetDefaults'])->name('reset');
    Route::post('/maintenance/enable', [\App\Http\Controllers\SuperAdmin\GlobalSettingsController::class, 'enableMaintenance'])->name('maintenance.enable');
    Route::post('/maintenance/disable', [\App\Http\Controllers\SuperAdmin\GlobalSettingsController::class, 'disableMaintenance'])->name('maintenance.disable');
    Route::post('/force-password-change', [\App\Http\Controllers\SuperAdmin\GlobalSettingsController::class, 'forcePasswordChange'])->name('force-password-change');
});

// Audit & Activity Centre 
Route::middleware(['auth', 'role:super_admin'])->prefix('super-admin/audit')->name('super-admin.audit.')->group(function () {
    Route::get('/', [\App\Http\Controllers\SuperAdmin\AuditCentreController::class, 'index'])->name('index');
    Route::get('/activity', [\App\Http\Controllers\SuperAdmin\AuditCentreController::class, 'activity'])->name('activity');
    Route::get('/activity/data', [\App\Http\Controllers\SuperAdmin\AuditCentreController::class, 'activityData'])->name('activity.data');
    Route::get('/security', [\App\Http\Controllers\SuperAdmin\AuditCentreController::class, 'security'])->name('security');
    Route::get('/security/data', [\App\Http\Controllers\SuperAdmin\AuditCentreController::class, 'securityData'])->name('security.data');
    Route::get('/impersonation', [\App\Http\Controllers\SuperAdmin\AuditCentreController::class, 'impersonation'])->name('impersonation');
    Route::get('/impersonation/data', [\App\Http\Controllers\SuperAdmin\AuditCentreController::class, 'impersonationData'])->name('impersonation.data');
    Route::get('/impersonation/{impersonation}', [\App\Http\Controllers\SuperAdmin\AuditCentreController::class, 'impersonationShow'])->name('impersonation.show')->where('impersonation', '[0-9]+');
    Route::get('/config-history', [\App\Http\Controllers\SuperAdmin\AuditCentreController::class, 'configHistory'])->name('config-history');
    Route::get('/config-history/data', [\App\Http\Controllers\SuperAdmin\AuditCentreController::class, 'configHistoryData'])->name('config-history.data');
    Route::get('/{view}/export/csv', [\App\Http\Controllers\SuperAdmin\AuditCentreController::class, 'exportCsv'])->name('export-csv')->where('view', '[a-z-]+');
    Route::get('/{view}/print', [\App\Http\Controllers\SuperAdmin\AuditCentreController::class, 'print'])->name('print')->where('view', '[a-z-]+');
});


Route::middleware(['auth', 'role:super_admin'])->prefix('super-admin/maintenance')->name('super-admin.maintenance.')->group(function () {
    Route::get('/', [\App\Http\Controllers\SuperAdmin\MaintenanceCentreController::class, 'index'])->name('index');
    Route::post('/cache/{type}/clear', [\App\Http\Controllers\SuperAdmin\MaintenanceCentreController::class, 'clearCache'])->name('cache.clear')->where('type', '[a-z]+');
    Route::get('/queue/failed-jobs', [\App\Http\Controllers\SuperAdmin\MaintenanceCentreController::class, 'failedJobsData'])->name('queue.failed-jobs');
    Route::post('/queue/retry', [\App\Http\Controllers\SuperAdmin\MaintenanceCentreController::class, 'retryFailedJobs'])->name('queue.retry');
    Route::post('/queue/clear', [\App\Http\Controllers\SuperAdmin\MaintenanceCentreController::class, 'clearFailedJobs'])->name('queue.clear');
    Route::get('/logs', [\App\Http\Controllers\SuperAdmin\MaintenanceCentreController::class, 'logs'])->name('logs');
    Route::get('/logs/data', [\App\Http\Controllers\SuperAdmin\MaintenanceCentreController::class, 'logsData'])->name('logs.data');
    Route::get('/logs/export/csv', [\App\Http\Controllers\SuperAdmin\MaintenanceCentreController::class, 'logsExportCsv'])->name('logs.export-csv');
    Route::get('/logs/print', [\App\Http\Controllers\SuperAdmin\MaintenanceCentreController::class, 'logsPrint'])->name('logs.print');
});

// Notification Centre (super_admin only) 
Route::middleware(['auth', 'role:super_admin'])->prefix('super-admin/notifications')->name('super-admin.notifications.')->group(function () {
    Route::get('/', [\App\Http\Controllers\SuperAdmin\NotificationCentreController::class, 'index'])->name('index');
    Route::get('/data', [\App\Http\Controllers\SuperAdmin\NotificationCentreController::class, 'data'])->name('data');
    Route::get('/bell', [\App\Http\Controllers\SuperAdmin\NotificationCentreController::class, 'bell'])->name('bell');
    Route::post('/mark-all-read', [\App\Http\Controllers\SuperAdmin\NotificationCentreController::class, 'markAllRead'])->name('mark-all-read');
    Route::post('/{id}/read', [\App\Http\Controllers\SuperAdmin\NotificationCentreController::class, 'markRead'])->name('read')->where('id', '[0-9a-f-]{36}');
    Route::delete('/{id}', [\App\Http\Controllers\SuperAdmin\NotificationCentreController::class, 'destroy'])->name('destroy')->where('id', '[0-9a-f-]{36}');
});

// Feature Management (super_admin only) — 
Route::middleware(['auth', 'role:super_admin'])->prefix('super-admin/features')->name('super-admin.features.')->group(function () {
    Route::get('/', [\App\Http\Controllers\SuperAdmin\FeatureManagementController::class, 'index'])->name('index');
    Route::get('/catalog', [\App\Http\Controllers\SuperAdmin\FeatureManagementController::class, 'catalog'])->name('catalog');
    Route::get('/catalog/data', [\App\Http\Controllers\SuperAdmin\FeatureManagementController::class, 'catalogData'])->name('catalog.data');
    Route::get('/groups', [\App\Http\Controllers\SuperAdmin\FeatureManagementController::class, 'groups'])->name('groups');
    Route::get('/plans', [\App\Http\Controllers\SuperAdmin\FeatureManagementController::class, 'plans'])->name('plans');
    Route::patch('/plans/{plan}', [\App\Http\Controllers\SuperAdmin\FeatureManagementController::class, 'updatePlanModules'])->name('plans.update');
    Route::get('/{module}', [\App\Http\Controllers\SuperAdmin\FeatureManagementController::class, 'show'])->name('show');
    Route::post('/{module}/enable', [\App\Http\Controllers\SuperAdmin\FeatureManagementController::class, 'enable'])->name('enable');
    Route::post('/{module}/disable', [\App\Http\Controllers\SuperAdmin\FeatureManagementController::class, 'disable'])->name('disable');
    Route::post('/{module}/hospitals/{hospital}/override', [\App\Http\Controllers\SuperAdmin\FeatureManagementController::class, 'setHospitalOverride'])->name('hospital-override');
});


Route::middleware(['auth'])->post('/impersonate/stop', [\App\Http\Controllers\SuperAdmin\ImpersonationController::class, 'stop'])->name('impersonate.stop');

// Public subscription status routes
Route::middleware(['auth'])->group(function () {
    Route::get('/subscription/current', [SubscriptionController::class, 'current'])->name('subscription.current');
    Route::get('/subscription/expired', [SubscriptionController::class, 'expired'])->name('subscription.expired');
    Route::get('/subscription/suspended', [SubscriptionController::class, 'suspended'])->name('subscription.suspended');
});

Route::middleware(['auth'])->post('/session/ping', function () {
    return response()->json(['status' => 'ok']);
})->name('session.ping');

