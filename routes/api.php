<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminRosterController;
use App\Http\Controllers\Api\AdminPayrollController;
use App\Http\Controllers\Api\AdminAccountsController;
use App\Http\Controllers\Api\PharmacyCounterController;
use App\Http\Controllers\Api\AdminSupplierController;
use App\Http\Controllers\Api\AdminWardController;
use App\Http\Controllers\Api\AdminDirectoryController;
use App\Http\Controllers\Api\AdminHrController;
use App\Http\Controllers\Api\AdminInventoryController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\DischargeSummaryController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\DoctorOrderController;
use App\Http\Controllers\Api\EmrController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\NursingAssessmentController;
use App\Http\Controllers\Api\ProgressNoteController;
use App\Http\Controllers\Api\LabAssistantController;
use App\Http\Controllers\Api\NurseController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PharmacyController;
use App\Http\Controllers\Api\ReceptionistController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Flutter app auth — issues/revokes Sanctum bearer tokens (not the
// stateful-cookie flow this file's `api` middleware group otherwise
// prepends EnsureFrontendRequestsAreStateful for; a mobile client has no
// matching Origin/Referer, so it falls through to token auth as intended).
// Throttled: these take credentials and are the app's only unauthenticated
// endpoints, so they're the ones worth guessing against. The web login is
// already rate-limited by Fortify; this is the same idea for the API.
Route::middleware('throttle:api-login')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
});
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:api-login');

// Everything behind a token also answers to the two gates the web has
// always had: an inactive subscription and a forced password change.
Route::middleware(['auth:sanctum', 'subscription', 'password.change'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::prefix('patient')->group(function () {
        Route::get('/appointments', [PatientController::class, 'appointments']);
        Route::post('/appointments', [PatientController::class, 'bookAppointment']);
        Route::post('/appointments/{appointment}/cancel', [PatientController::class, 'cancelAppointment']);
        Route::put('/appointments/{appointment}/reschedule', [PatientController::class, 'rescheduleAppointment']);
        Route::get('/doctors', [PatientController::class, 'doctors']);
        Route::get('/slots', [PatientController::class, 'slots']);
        Route::get('/prescriptions', [PatientController::class, 'prescriptions']);
        Route::get('/lab-results', [PatientController::class, 'labResults']);
        Route::get('/invoices', [PatientController::class, 'invoices']);
        Route::post('/invoices/{invoice}/request-help', [PatientController::class, 'requestInvoiceHelp']);
        Route::get('/health-records', [PatientController::class, 'healthRecords']);
        Route::get('/profile', [PatientController::class, 'profile']);
        Route::post('/profile', [PatientController::class, 'updateProfile']);
    });

    Route::prefix('nurse')->group(function () {
        Route::get('/dashboard', [NurseController::class, 'dashboard']);

        Route::get('/patients', [NurseController::class, 'patients']);
        Route::get('/patients/{patient}', [NurseController::class, 'patientDetail']);

        Route::get('/wards', [NurseController::class, 'wards']);
        Route::get('/wards/available-beds', [NurseController::class, 'availableBeds']);
        Route::get('/wards/{ward}', [NurseController::class, 'wardDetail']);

        Route::get('/admissions', [NurseController::class, 'admissions']);
        Route::post('/admissions', [NurseController::class, 'admit']);
        Route::put('/admissions/{admission}/discharge', [NurseController::class, 'dischargeAdmission']);
        Route::put('/admissions/{admission}/transfer', [NurseController::class, 'transferAdmission']);
        Route::get('/admissions/{admission}/balance', [NurseController::class, 'admissionBalance']);

        Route::get('/emar', [NurseController::class, 'emarList']);
        Route::post('/emar', [NurseController::class, 'emarStore']);
        Route::put('/emar/{mar}/administer', [NurseController::class, 'emarAdminister']);
        Route::delete('/emar/{mar}', [NurseController::class, 'emarDestroy']);

        Route::get('/vitals', [NurseController::class, 'vitalsList']);
        Route::post('/vitals', [NurseController::class, 'vitalsStore']);
        Route::get('/vitals/patient/{patient}', [NurseController::class, 'vitalsHistory']);
        Route::delete('/vitals/{vital}', [NurseController::class, 'vitalsDestroy']);

        Route::get('/nursing-notes', [NurseController::class, 'nursingNotesList']);
        Route::post('/nursing-notes', [NurseController::class, 'nursingNoteStore']);
        Route::put('/nursing-notes/{note}', [NurseController::class, 'nursingNoteUpdate']);
        Route::delete('/nursing-notes/{note}', [NurseController::class, 'nursingNoteDestroy']);

        Route::get('/shift-handovers', [NurseController::class, 'handoversList']);
        Route::get('/shift-handovers/ward-patients', [NurseController::class, 'handoverWardPatients']);
        Route::get('/shift-handovers/create-meta', [NurseController::class, 'handoverCreateMeta']);
        Route::get('/shift-handovers/alert-count', [NurseController::class, 'handoverAlertCount']);
        Route::post('/shift-handovers', [NurseController::class, 'handoverStore']);
        Route::get('/shift-handovers/{shiftHandover}', [NurseController::class, 'handoverDetail']);
        Route::post('/shift-handovers/{shiftHandover}/submit', [NurseController::class, 'handoverSubmit']);
        Route::post('/shift-handovers/{shiftHandover}/accept', [NurseController::class, 'handoverAccept']);
        Route::post('/shift-handovers/{shiftHandover}/reject', [NurseController::class, 'handoverReject']);
        Route::delete('/shift-handovers/{shiftHandover}', [NurseController::class, 'handoverDestroy']);

        Route::get('/ot', [NurseController::class, 'otList']);
        Route::post('/ot', [NurseController::class, 'otStore']);
        Route::get('/ot/{ot}', [NurseController::class, 'otDetail']);
        Route::put('/ot/{ot}/start', [NurseController::class, 'otStart']);
        Route::put('/ot/{ot}/complete', [NurseController::class, 'otComplete']);
        Route::delete('/ot/{ot}', [NurseController::class, 'otDestroy']);

        Route::get('/icu', [NurseController::class, 'icuList']);
        Route::post('/icu', [NurseController::class, 'icuStore']);
        Route::get('/icu/{chart}', [NurseController::class, 'icuDetail']);
        Route::delete('/icu/{chart}', [NurseController::class, 'icuDestroy']);

        Route::get('/blood-bank', [NurseController::class, 'bloodBankIndex']);
        Route::post('/blood-bank', [NurseController::class, 'bloodBankStore']);
        Route::get('/blood-bank/transfusions', [NurseController::class, 'bloodBankTransfusions']);
        Route::put('/blood-bank/{unit}/transfuse', [NurseController::class, 'bloodBankTransfuse']);
        Route::put('/blood-bank/{unit}/status', [NurseController::class, 'bloodBankUpdateStatus']);
        Route::delete('/blood-bank/{unit}', [NurseController::class, 'bloodBankDestroy']);

        Route::get('/consent-forms', [NurseController::class, 'consentFormsList']);
        Route::post('/consent-forms', [NurseController::class, 'consentFormStore']);
        Route::post('/consent-forms/{consentForm}/signature', [NurseController::class, 'consentFormSign']);
        Route::put('/consent-forms/{consentForm}/status', [NurseController::class, 'consentFormUpdateStatus']);
        Route::delete('/consent-forms/{consentForm}', [NurseController::class, 'consentFormDestroy']);

        Route::get('/er', [NurseController::class, 'erList']);
        Route::post('/er', [NurseController::class, 'erStore']);
        Route::get('/er/{er}', [NurseController::class, 'erDetail']);
        Route::put('/er/{er}', [NurseController::class, 'erUpdate']);
        Route::put('/er/{er}/triage', [NurseController::class, 'erTriage']);
        Route::put('/er/{er}/discharge', [NurseController::class, 'erDischarge']);
        Route::delete('/er/{er}', [NurseController::class, 'erDestroy']);

        Route::get('/food-intake', [NurseController::class, 'foodIntakeList']);
        Route::get('/food-intake/{patient}', [NurseController::class, 'foodIntakeDetail']);
        Route::post('/food-intake/{patient}/diet-plan', [NurseController::class, 'foodIntakeStoreDietPlan']);
        Route::post('/food-intake/{patient}/log-meal', [NurseController::class, 'foodIntakeLogMeal']);

        Route::get('/fluid-intake', [NurseController::class, 'fluidIntakeList']);
        Route::get('/fluid-intake/{patient}', [NurseController::class, 'fluidIntakeDetail']);
        Route::post('/fluid-intake/{patient}/log', [NurseController::class, 'fluidIntakeLog']);
        Route::delete('/fluid-logs/{fluidLog}', [NurseController::class, 'fluidIntakeDestroy']);

        Route::get('/my-rounds', [NurseController::class, 'myRounds']);
        Route::get('/rounds/alert-count', [NurseController::class, 'roundsAlertCount']);
        Route::post('/rounds/{patient}/mark', [NurseController::class, 'markRound']);
        Route::get('/rounds/{patient}/history', [NurseController::class, 'roundHistory']);
        Route::get('/staff-rounds/assignments', [NurseController::class, 'staffRoundsAssignments']);

        Route::get('/profile', [NurseController::class, 'profile']);
        Route::post('/profile', [NurseController::class, 'updateProfile']);
    });

    // Staff role — its own dashboard; everything else (Patients, Food/Fluid
    // Intake, Rounds, Profile) reuses the /nurse/* routes above, same as the
    // web reuses the same controllers/routes for both roles.
    Route::prefix('staff')->group(function () {
        Route::get('/dashboard', [NurseController::class, 'staffDashboard']);

        // The same two the nurse uses: their guard is authNurseOrStaff,
        // so a staff account was always allowed to call them -- it just
        // had no path of its own, and so no profile screen in the app.
        // A staff member could not change their own name, password or
        // picture at all.
        Route::get('/profile', [NurseController::class, 'profile']);
        Route::post('/profile', [NurseController::class, 'updateProfile']);
    });

    Route::prefix('doctor')->group(function () {
        Route::get('/dashboard', [DoctorController::class, 'dashboard']);

        Route::get('/appointments', [DoctorController::class, 'appointments']);
        Route::get('/appointments/{appointment}', [DoctorController::class, 'appointmentDetail']);
        Route::post('/appointments/{appointment}/consultation', [DoctorController::class, 'consultationStore']);

        Route::get('/consultations/{consultation}', [DoctorController::class, 'consultationDetail']);
        Route::post('/consultations/{consultation}/prescription', [DoctorController::class, 'prescriptionStore']);
        Route::get('/prescriptions', [DoctorController::class, 'prescriptions']);

        Route::get('/patients', [DoctorController::class, 'patients']);
        Route::get('/patients/{patient}', [DoctorController::class, 'patientDetail']);

        Route::get('/icd10', [DoctorController::class, 'icd10Search']);

        // Everything a doctor orders for a patient, in one place.
        Route::get('/orders/{patient}', [DoctorOrderController::class, 'index']);
        Route::post('/orders/{patient}/lab', [DoctorOrderController::class, 'storeLab']);
        Route::post('/orders/{patient}/radiology', [DoctorOrderController::class, 'storeRadiology']);
        Route::post('/orders/{patient}/medicines', [DoctorOrderController::class, 'storeMedicines']);
        Route::post('/orders/{patient}/diet', [DoctorOrderController::class, 'storeDiet']);

        Route::get('/schedule', [DoctorController::class, 'schedule']);
        Route::post('/schedule', [DoctorController::class, 'scheduleStore']);
        Route::put('/schedule/{schedule}', [DoctorController::class, 'scheduleUpdate']);
        Route::delete('/schedule/{schedule}', [DoctorController::class, 'scheduleDestroy']);

        Route::get('/profile', [DoctorController::class, 'profile']);
        Route::post('/profile', [DoctorController::class, 'updateProfile']);
    });

    // The ward round: who has not been seen today, and the note.
    Route::prefix('rounds')->group(function () {
        Route::get('/', [ProgressNoteController::class, 'index']);
        Route::get('/{admission}', [ProgressNoteController::class, 'show']);
        Route::post('/{admission}/notes', [ProgressNoteController::class, 'store']);
        Route::delete('/notes/{note}', [ProgressNoteController::class, 'destroy']);
    });

    // The document a patient leaves with, gathered from the stay.
    Route::prefix('discharge-summaries')->group(function () {
        Route::get('/', [DischargeSummaryController::class, 'index']);
        Route::get('/{admission}', [DischargeSummaryController::class, 'show']);
        Route::post('/{admission}', [DischargeSummaryController::class, 'store']);
    });

    // Scored bedside assessments, shared by nurses and doctors.
    Route::prefix('assessments')->group(function () {
        Route::get('/', [NursingAssessmentController::class, 'index']);
        Route::post('/patients/{patient}', [NursingAssessmentController::class, 'store']);
        Route::delete('/{assessment}', [NursingAssessmentController::class, 'destroy']);
    });

    // The patient's medical record -- shared by the clinical roles, the
    // way the web's EMR pages are.
    // What the signed-in account can see about itself, whatever its role.
    Route::prefix('me')->group(function () {
        Route::get('/attendance', [MeController::class, 'attendance']);
        Route::get('/leaves', [MeController::class, 'leaves']);
        Route::post('/leaves', [MeController::class, 'leaveStore']);
        Route::delete('/leaves/{leave}', [MeController::class, 'leaveDestroy']);
        Route::get('/notifications', [MeController::class, 'notifications']);
        Route::post('/notifications/read', [MeController::class, 'notificationsRead']);

        // Not /search: routes/web.php already serves an api/search for
        // the web app's own search box, and it wins the URL.
        Route::get('/search', [MeController::class, 'search']);
    });

    Route::prefix('emr')->group(function () {
        Route::get('/patients/{patient}', [EmrController::class, 'show']);

        Route::post('/patients/{patient}/allergies', [EmrController::class, 'storeAllergy']);
        Route::delete('/allergies/{allergy}', [EmrController::class, 'destroyAllergy']);

        Route::post('/patients/{patient}/problems', [EmrController::class, 'storeProblem']);
        Route::put('/problems/{problem}', [EmrController::class, 'updateProblem']);
        Route::delete('/problems/{problem}', [EmrController::class, 'destroyProblem']);

        Route::post('/patients/{patient}/records', [EmrController::class, 'storeRecord']);
        Route::delete('/records/{record}', [EmrController::class, 'destroyRecord']);

        Route::post('/patients/{patient}/attachments', [EmrController::class, 'storeAttachment']);
        Route::delete('/attachments/{attachment}', [EmrController::class, 'destroyAttachment']);
    });

    Route::prefix('lab')->group(function () {
        Route::get('/dashboard', [LabAssistantController::class, 'dashboard']);

        Route::get('/tests', [LabAssistantController::class, 'labTests']);
        Route::post('/tests', [LabAssistantController::class, 'labTestStore']);
        Route::put('/tests/{labTest}', [LabAssistantController::class, 'labTestUpdate']);
        Route::delete('/tests/{labTest}', [LabAssistantController::class, 'labTestDestroy']);

        // Sample collection -- the step between ordering a test and
        // having a result.
        Route::get('/samples', [LabAssistantController::class, 'samples']);
        Route::get('/samples/barcode', [LabAssistantController::class, 'sampleByBarcode']);
        Route::post('/orders/{labOrder}/collect-sample', [LabAssistantController::class, 'collectSample']);

        Route::get('/orders', [LabAssistantController::class, 'labOrders']);
        Route::post('/orders', [LabAssistantController::class, 'labOrderStore']);
        Route::get('/orders/{labOrder}', [LabAssistantController::class, 'labOrderDetail']);
        Route::post('/orders/{labOrder}/results', [LabAssistantController::class, 'labOrderEnterResults']);

        Route::get('/patients', [LabAssistantController::class, 'patients']);

        Route::get('/profile', [LabAssistantController::class, 'profile']);
        Route::post('/profile', [LabAssistantController::class, 'updateProfile']);
    });

    /*
     * The administrator's own endpoints. Everything else an admin touches
     * — patients, wards, billing, pharmacy, the lab — runs on the clinical
     * roles' routes, which AuthorizesRoles already lets an admin through.
     * Only what has no clinical owner is here.
     */
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);

        Route::get('/reports/patients', [AdminController::class, 'patientReport']);
        Route::get('/reports/billing', [AdminController::class, 'billingReport']);
        Route::get('/reports/inventory', [AdminController::class, 'inventoryReport']);

        Route::get('/settings', [AdminController::class, 'settings']);
        Route::put('/settings', [AdminController::class, 'updateSettings']);

        Route::get('/departments', [AdminController::class, 'departments']);
        Route::post('/departments', [AdminController::class, 'departmentStore']);
        Route::put('/departments/{department}', [AdminController::class, 'departmentUpdate']);
        Route::delete('/departments/{department}', [AdminController::class, 'departmentDestroy']);

        Route::get('/activity-log', [AdminController::class, 'activityLog']);

        Route::get('/profile', [AdminController::class, 'profile']);
        Route::post('/profile', [AdminController::class, 'updateProfile']);

        Route::get('/doctors', [AdminDirectoryController::class, 'doctors']);
        Route::post('/doctors', [AdminDirectoryController::class, 'doctorStore']);
        Route::get('/doctors/{doctor}', [AdminDirectoryController::class, 'doctorDetail']);
        Route::put('/doctors/{doctor}', [AdminDirectoryController::class, 'doctorUpdate']);
        Route::delete('/doctors/{doctor}', [AdminDirectoryController::class, 'doctorDestroy']);

        Route::get('/users', [AdminDirectoryController::class, 'users']);
        Route::post('/users', [AdminDirectoryController::class, 'userStore']);
        Route::put('/users/{user}', [AdminDirectoryController::class, 'userUpdate']);
        Route::put('/users/{user}/password', [AdminDirectoryController::class, 'userResetPassword']);
        Route::delete('/users/{user}', [AdminDirectoryController::class, 'userDestroy']);

        Route::get('/staff', [AdminDirectoryController::class, 'staff']);

        Route::get('/inventory', [AdminInventoryController::class, 'items']);
        Route::post('/inventory', [AdminInventoryController::class, 'itemStore']);
        Route::put('/inventory/{item}', [AdminInventoryController::class, 'itemUpdate']);
        Route::delete('/inventory/{item}', [AdminInventoryController::class, 'itemDestroy']);

        Route::get('/purchase-orders', [AdminInventoryController::class, 'purchaseOrders']);
        Route::post('/purchase-orders', [AdminInventoryController::class, 'purchaseOrderStore']);
        Route::put('/purchase-orders/{po}/receive', [AdminInventoryController::class, 'purchaseOrderReceive']);
        Route::delete('/purchase-orders/{po}', [AdminInventoryController::class, 'purchaseOrderDestroy']);

        Route::get('/services', [AdminInventoryController::class, 'services']);
        Route::post('/services', [AdminInventoryController::class, 'serviceStore']);
        Route::put('/services/{billingService}', [AdminInventoryController::class, 'serviceUpdate']);
        Route::delete('/services/{billingService}', [AdminInventoryController::class, 'serviceDestroy']);

        Route::get('/attendance', [AdminHrController::class, 'attendance']);
        Route::post('/attendance', [AdminHrController::class, 'attendanceStore']);
        Route::put('/attendance/{attendance}', [AdminHrController::class, 'attendanceUpdate']);

        Route::get('/leaves', [AdminHrController::class, 'leaves']);
        Route::post('/leaves', [AdminHrController::class, 'leaveStore']);
        Route::put('/leaves/{leave}/approve', [AdminHrController::class, 'leaveApprove']);
        Route::put('/leaves/{leave}/reject', [AdminHrController::class, 'leaveReject']);
        Route::delete('/leaves/{leave}', [AdminHrController::class, 'leaveDestroy']);

        // Roster, payroll and the books -- the three the hospital ran
        // on paper.
        Route::get('/roster', [AdminRosterController::class, 'index']);
        Route::post('/roster', [AdminRosterController::class, 'store']);
        Route::delete('/roster/{roster}', [AdminRosterController::class, 'destroy']);

        Route::get('/payroll', [AdminPayrollController::class, 'index']);
        Route::post('/payroll', [AdminPayrollController::class, 'store']);
        Route::put('/payroll/{payroll}/pay', [AdminPayrollController::class, 'pay']);
        Route::delete('/payroll/{payroll}', [AdminPayrollController::class, 'destroy']);

        Route::get('/expenses', [AdminAccountsController::class, 'expenses']);
        Route::post('/expenses', [AdminAccountsController::class, 'storeExpense']);
        Route::delete('/expenses/{expense}', [AdminAccountsController::class, 'destroyExpense']);
        Route::get('/day-end', [AdminAccountsController::class, 'dayEnd']);
        Route::post('/day-end', [AdminAccountsController::class, 'closeDay']);
        Route::get('/closings', [AdminAccountsController::class, 'closings']);

        // The vendor master behind purchase orders.
        Route::get('/suppliers', [AdminSupplierController::class, 'index']);
        Route::post('/suppliers', [AdminSupplierController::class, 'store']);
        Route::put('/suppliers/{supplier}', [AdminSupplierController::class, 'update']);
        Route::delete('/suppliers/{supplier}', [AdminSupplierController::class, 'destroy']);

        // Wards and beds -- a web-only page until now.
        Route::get('/wards', [AdminWardController::class, 'index']);
        Route::post('/wards', [AdminWardController::class, 'store']);
        Route::get('/wards/{ward}', [AdminWardController::class, 'show']);
        Route::put('/wards/{ward}', [AdminWardController::class, 'update']);
        Route::delete('/wards/{ward}', [AdminWardController::class, 'destroy']);
        Route::post('/wards/{ward}/beds', [AdminWardController::class, 'storeBed']);
        Route::put('/beds/{bed}', [AdminWardController::class, 'updateBed']);
        Route::delete('/beds/{bed}', [AdminWardController::class, 'destroyBed']);

        Route::get('/notifications', [AdminHrController::class, 'notifications']);
        Route::get('/notification-templates', [AdminHrController::class, 'templates']);
        Route::post('/notification-templates', [AdminHrController::class, 'templateStore']);
        Route::put('/notification-templates/{template}', [AdminHrController::class, 'templateUpdate']);
    });

    Route::prefix('receptionist')->group(function () {
        Route::get('/dashboard', [ReceptionistController::class, 'dashboard']);

        Route::get('/patients', [ReceptionistController::class, 'patients']);
        Route::post('/patients', [ReceptionistController::class, 'patientStore']);
        Route::get('/patients/{patient}', [ReceptionistController::class, 'patientDetail']);
        Route::post('/patients/{patient}', [ReceptionistController::class, 'patientUpdate']);

        Route::get('/appointments', [ReceptionistController::class, 'appointments']);
        Route::post('/appointments', [ReceptionistController::class, 'appointmentStore']);
        Route::put('/appointments/{appointment}', [ReceptionistController::class, 'appointmentUpdate']);
        Route::get('/appointments/slots', [ReceptionistController::class, 'appointmentSlots']);
        Route::get('/doctors', [ReceptionistController::class, 'doctors']);

        Route::get('/referrals', [ReceptionistController::class, 'referrals']);
        Route::post('/referrals', [ReceptionistController::class, 'referralStore']);
        Route::put('/referrals/{referral}/status', [ReceptionistController::class, 'referralUpdateStatus']);
        Route::delete('/referrals/{referral}', [ReceptionistController::class, 'referralDestroy']);

        Route::get('/opd-queue', [ReceptionistController::class, 'opdQueue']);
        Route::post('/opd-queue', [ReceptionistController::class, 'opdQueueStore']);
        Route::put('/opd-queue/call-next/{doctor}', [ReceptionistController::class, 'opdQueueCallNext']);
        Route::put('/opd-queue/{token}/status', [ReceptionistController::class, 'opdQueueUpdateStatus']);
        Route::delete('/opd-queue/{token}', [ReceptionistController::class, 'opdQueueDestroy']);

        Route::get('/radiology', [ReceptionistController::class, 'radiologyOrders']);
        Route::post('/radiology', [ReceptionistController::class, 'radiologyStore']);
        Route::get('/radiology/{order}', [ReceptionistController::class, 'radiologyDetail']);
        Route::put('/radiology/{order}', [ReceptionistController::class, 'radiologyUpdate']);
        Route::put('/radiology/{order}/findings', [ReceptionistController::class, 'radiologyEnterFindings']);
        Route::delete('/radiology/{order}', [ReceptionistController::class, 'radiologyDestroy']);

        Route::get('/profile', [ReceptionistController::class, 'profile']);
        Route::post('/profile', [ReceptionistController::class, 'updateProfile']);

        // Ward/Admissions/Consent Forms/ER/Lab reuse the nurse+lab controllers
        // (shared role middleware on the web) — see the /nurse and /lab
        // groups above; NurseController/LabAssistantController's
        // authNurseOrReceptionist()/authLabAssistantOrReceptionist() helpers
        // already allow this role in, no separate routes needed here.

        Route::get('/billing/invoices/stats', [BillingController::class, 'invoicesStats']);
        Route::get('/billing/invoices', [BillingController::class, 'invoices']);
        Route::get('/billing/invoices/services', [BillingController::class, 'invoiceServices']);
        Route::post('/billing/invoices', [BillingController::class, 'invoiceStore']);
        Route::get('/billing/invoices/{invoice}', [BillingController::class, 'invoiceDetail']);
        Route::post('/billing/invoices/{invoice}/payment', [BillingController::class, 'invoiceAddPayment']);

        Route::get('/billing/insurance-claims', [BillingController::class, 'insuranceClaims']);
        Route::post('/billing/insurance-claims', [BillingController::class, 'insuranceClaimStore']);
        Route::get('/billing/insurance-claims/{claim}', [BillingController::class, 'insuranceClaimDetail']);
        Route::put('/billing/insurance-claims/{claim}', [BillingController::class, 'insuranceClaimUpdate']);
        Route::put('/billing/insurance-claims/{claim}/status', [BillingController::class, 'insuranceClaimUpdateStatus']);
        Route::delete('/billing/insurance-claims/{claim}', [BillingController::class, 'insuranceClaimDestroy']);

        Route::get('/billing/ipd-deposits', [BillingController::class, 'ipdDeposits']);
        Route::post('/billing/ipd-deposits', [BillingController::class, 'ipdDepositStore']);
        Route::get('/billing/ipd-deposits/{deposit}', [BillingController::class, 'ipdDepositShow']);
        Route::post('/billing/ipd-deposits/{deposit}/refund', [BillingController::class, 'ipdDepositRefund']);

        Route::get('/billing/scheme-billing', [BillingController::class, 'schemeBillings']);
        Route::post('/billing/scheme-billing', [BillingController::class, 'schemeBillingStore']);
        Route::get('/billing/scheme-billing/{scheme}', [BillingController::class, 'schemeBillingDetail']);
        Route::put('/billing/scheme-billing/{scheme}', [BillingController::class, 'schemeBillingUpdate']);
        Route::put('/billing/scheme-billing/{scheme}/status', [BillingController::class, 'schemeBillingUpdateStatus']);
        Route::delete('/billing/scheme-billing/{scheme}', [BillingController::class, 'schemeBillingDestroy']);
    });

    Route::prefix('pharmacy')->group(function () {
        // The counter: walk-in sales and medicine brought back.
        Route::get('/counter', [PharmacyCounterController::class, 'index']);
        Route::post('/counter/sell', [PharmacyCounterController::class, 'sell']);
        Route::get('/counter/returnable', [PharmacyCounterController::class, 'returnable']);
        Route::post('/counter/{dispensing}/return', [PharmacyCounterController::class, 'returnItems']);

        Route::get('/dashboard', [PharmacyController::class, 'dashboard']);

        Route::get('/drugs', [PharmacyController::class, 'drugs']);
        Route::post('/drugs', [PharmacyController::class, 'drugStore']);
        Route::put('/drugs/{drug}', [PharmacyController::class, 'drugUpdate']);
        Route::delete('/drugs/{drug}', [PharmacyController::class, 'drugDestroy']);
        Route::post('/drugs/{drug}/stock', [PharmacyController::class, 'drugAddStock']);
        Route::get('/drugs/{drug}/stock-history', [PharmacyController::class, 'drugStockHistory']);

        Route::get('/dispensing', [PharmacyController::class, 'dispensingList']);
        Route::post('/dispensing', [PharmacyController::class, 'dispensingStore']);
        Route::get('/dispensing/alerts', [PharmacyController::class, 'dispensingAlerts']);
        Route::get('/dispensing/from-prescription/{prescription}', [PharmacyController::class, 'dispensingFromPrescription']);
        Route::get('/dispensing/{dispensing}', [PharmacyController::class, 'dispensingDetail']);

        Route::get('/patients', [PharmacyController::class, 'patients']);

        Route::get('/profile', [PharmacyController::class, 'profile']);
        Route::post('/profile', [PharmacyController::class, 'updateProfile']);
    });
});

// The navbar global-search endpoint used to live here as `/search` (->'auth').
// Moved to routes/web.php as `/api/search` — see the comment there for why:
// this file's `api` middleware group only authenticates via Sanctum's
// stateful-SPA cookie flow, gated by config('sanctum.stateful') matching the
// request's Origin/Referer host, which doesn't cover arbitrary local dev
// ports. That silently broke the search box for every request.
