<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ChairController;
use App\Http\Controllers\ClinicSettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\OnlineBookingRequestController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\ReceptionDeskController;
use App\Http\Controllers\StaffAccountController;
use App\Http\Controllers\StaffScheduleController;
use App\Http\Controllers\TreatmentPlanController;
use App\Http\Controllers\TreatmentServiceController;
use App\Http\Controllers\TreatmentTaskController;
use App\Http\Controllers\WorkShiftController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicBookingController::class, 'welcome'])->name('home');

Route::middleware('throttle:30,1')->prefix('book')->name('booking.')->group(function () {
    Route::get('/', [PublicBookingController::class, 'services'])->name('services');
    Route::get('/services/{service}', [PublicBookingController::class, 'service'])->whereNumber('service')->name('service');
    Route::get('/services/{service}/slots', [PublicBookingController::class, 'slots'])->whereNumber('service')->name('slots');
    Route::get('/services/{service}/request', [PublicBookingController::class, 'form'])->whereNumber('service')->name('form');
    Route::get('/confirmation/{reference}', [PublicBookingController::class, 'confirmation'])
        ->where('reference', '[0-9a-fA-F-]{36}')
        ->name('confirmation');
});

Route::post('/book', [PublicBookingController::class, 'store'])
    ->middleware('throttle:8,1')
    ->name('booking.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:admin,receptionist,specialist,clinical_staff'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});

Route::middleware(['auth', 'role:admin,receptionist'])->group(function () {
    Route::get('/patients/create', [PatientController::class, 'create'])->name('patients.create');
    Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');

    Route::get('/booking-requests', [OnlineBookingRequestController::class, 'index'])->name('booking.requests.index');
    Route::get('/booking-requests/{appointment}', [OnlineBookingRequestController::class, 'show'])->whereNumber('appointment')->name('booking.requests.show');
    Route::post('/booking-requests/{appointment}/approve', [OnlineBookingRequestController::class, 'approve'])->whereNumber('appointment')->name('booking.requests.approve');
    Route::post('/booking-requests/{appointment}/reject', [OnlineBookingRequestController::class, 'reject'])->whereNumber('appointment')->name('booking.requests.reject');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/staff/{directory}/create', [StaffAccountController::class, 'create'])
        ->whereIn('directory', ['specialists', 'clinical-staff', 'receptionists'])
        ->name('staff.create');
    Route::post('/staff/{directory}', [StaffAccountController::class, 'store'])
        ->whereIn('directory', ['specialists', 'clinical-staff', 'receptionists'])
        ->name('staff.store');
    Route::get('/chairs/create', [ChairController::class, 'create'])->name('chairs.create');
    Route::post('/chairs', [ChairController::class, 'store'])->name('chairs.store');
    Route::get('/services/create', [TreatmentServiceController::class, 'create'])->name('services.create');
    Route::post('/services', [TreatmentServiceController::class, 'store'])->name('services.store');
    Route::get('/settings', [ClinicSettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [ClinicSettingController::class, 'update'])->name('settings.update');

    Route::get('/shifts', [WorkShiftController::class, 'index'])->name('shifts.index');
    Route::get('/shifts/create', [WorkShiftController::class, 'create'])->name('shifts.create');
    Route::post('/shifts', [WorkShiftController::class, 'store'])->name('shifts.store');
    Route::get('/shifts/{shift}/edit', [WorkShiftController::class, 'edit'])->whereNumber('shift')->name('shifts.edit');
    Route::put('/shifts/{shift}', [WorkShiftController::class, 'update'])->whereNumber('shift')->name('shifts.update');
    Route::post('/shifts/{shift}/breaks', [WorkShiftController::class, 'storeBreak'])->whereNumber('shift')->name('shifts.breaks.store');
    Route::delete('/shifts/{shift}/breaks/{shiftBreak}', [WorkShiftController::class, 'destroyBreak'])->whereNumber(['shift', 'shiftBreak'])->name('shifts.breaks.destroy');

    Route::get('/holidays', [HolidayController::class, 'index'])->name('holidays.index');
    Route::post('/holidays', [HolidayController::class, 'store'])->name('holidays.store');
    Route::delete('/holidays/{holiday}', [HolidayController::class, 'destroy'])->whereNumber('holiday')->name('holidays.destroy');

    Route::get('/schedules', [StaffScheduleController::class, 'index'])->name('schedules.index');
    Route::post('/schedules', [StaffScheduleController::class, 'store'])->name('schedules.store');
    Route::delete('/schedules/{staffSchedule}', [StaffScheduleController::class, 'destroy'])->whereNumber('staffSchedule')->name('schedules.destroy');
});

Route::middleware(['auth', 'role:admin,receptionist'])->group(function () {
    Route::get('/reception/search', [ReceptionDeskController::class, 'search'])->name('reception.search');
});

Route::middleware(['auth', 'role:admin,receptionist,specialist'])->group(function () {
    Route::get('/reception', [ReceptionDeskController::class, 'day'])->name('reception.day');
    Route::get('/reception/week', [ReceptionDeskController::class, 'week'])->name('reception.week');
    Route::get('/reception/visits/{visit}', [ReceptionDeskController::class, 'showVisit'])->whereNumber('visit')->name('reception.visits.show');

    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->whereNumber('appointment')->name('appointments.show');

    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/patients/{patient}/clinical-notes', [PatientController::class, 'clinicalNotes'])->withTrashed()->name('patients.clinical-notes');
    Route::get('/patients/{patient}', [PatientController::class, 'show'])->withTrashed()->name('patients.show');

    Route::get('/chairs', [ChairController::class, 'index'])->name('chairs.index');
    Route::get('/services', [TreatmentServiceController::class, 'index'])->name('services.index');

    Route::get('/staff/{directory}', [StaffAccountController::class, 'index'])
        ->whereIn('directory', ['specialists', 'clinical-staff', 'receptionists'])
        ->name('staff.index');
    Route::get('/staff/{directory}/{account}', [StaffAccountController::class, 'show'])
        ->whereIn('directory', ['specialists', 'clinical-staff', 'receptionists'])
        ->name('staff.show');

    Route::get('/clinical/plans', [TreatmentPlanController::class, 'index'])->name('clinical.plans.index');
    Route::get('/clinical/plans/{plan}', [TreatmentPlanController::class, 'show'])->whereNumber('plan')->name('clinical.plans.show');
    Route::get('/follow-ups', [FollowUpController::class, 'index'])->name('clinical.follow-ups.index');
    Route::get('/follow-ups/{followUp}', [FollowUpController::class, 'show'])->whereNumber('followUp')->name('clinical.follow-ups.show');
});

Route::middleware(['auth', 'role:admin,specialist'])->group(function () {
    Route::get('/patients/{patient}/clinical/plans/create', [TreatmentPlanController::class, 'create'])->whereNumber('patient')->name('clinical.plans.create');
    Route::post('/patients/{patient}/clinical/plans', [TreatmentPlanController::class, 'store'])
        ->middleware('throttle:30,1')
        ->whereNumber('patient')
        ->name('clinical.plans.store');
    Route::put('/clinical/plans/{plan}', [TreatmentPlanController::class, 'update'])
        ->middleware('throttle:30,1')
        ->whereNumber('plan')
        ->name('clinical.plans.update');
    Route::post('/clinical/plans/{plan}/tasks', [TreatmentPlanController::class, 'storeTask'])
        ->middleware('throttle:30,1')
        ->whereNumber('plan')
        ->name('clinical.plans.tasks.store');

    Route::get('/patients/{patient}/follow-ups/create', [FollowUpController::class, 'create'])->whereNumber('patient')->name('clinical.follow-ups.create');
    Route::post('/patients/{patient}/follow-ups', [FollowUpController::class, 'store'])
        ->middleware('throttle:30,1')
        ->whereNumber('patient')
        ->name('clinical.follow-ups.store');
});

Route::middleware(['auth', 'role:admin,receptionist,specialist,clinical_staff'])->group(function () {
    Route::get('/clinical/tasks', [TreatmentTaskController::class, 'index'])->name('clinical.tasks.index');
    Route::get('/clinical/tasks/{task}', [TreatmentTaskController::class, 'show'])->whereNumber('task')->name('clinical.tasks.show');
    Route::post('/clinical/tasks/{task}/assign', [TreatmentTaskController::class, 'assign'])
        ->middleware('throttle:30,1')
        ->whereNumber('task')
        ->name('clinical.tasks.assign');
    Route::post('/clinical/tasks/{task}/status', [TreatmentTaskController::class, 'updateStatus'])
        ->middleware('throttle:40,1')
        ->whereNumber('task')
        ->name('clinical.tasks.status');
});

Route::middleware(['auth', 'role:admin,receptionist'])->group(function () {
    Route::post('/follow-ups/{followUp}/offer', [FollowUpController::class, 'markOffered'])
        ->middleware('throttle:30,1')
        ->whereNumber('followUp')
        ->name('clinical.follow-ups.offer');
    Route::post('/follow-ups/{followUp}/decline', [FollowUpController::class, 'decline'])
        ->middleware('throttle:30,1')
        ->whereNumber('followUp')
        ->name('clinical.follow-ups.decline');
});

Route::middleware(['auth', 'role:admin,receptionist'])->group(function () {
    Route::post('/reception/appointments/{appointment}/check-in', [ReceptionDeskController::class, 'checkIn'])->whereNumber('appointment')->name('reception.check-in');
    Route::post('/reception/queue/call-next', [ReceptionDeskController::class, 'callNext'])->name('reception.queue.call-next');
    Route::post('/reception/visits/{visit}/start', [ReceptionDeskController::class, 'startVisit'])->whereNumber('visit')->name('reception.visits.start');
    Route::post('/reception/queue/{entry}/remove', [ReceptionDeskController::class, 'removeQueueEntry'])->whereNumber('entry')->name('reception.queue.remove');

    Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->whereNumber('appointment')->name('appointments.reschedule');
    Route::post('/appointments/{appointment}/reschedule', [AppointmentController::class, 'storeReschedule'])->whereNumber('appointment')->name('appointments.reschedule.store');
    Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->whereNumber('appointment')->name('appointments.cancel');

    Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])->withTrashed()->name('patients.edit');
    Route::put('/patients/{patient}', [PatientController::class, 'update'])->withTrashed()->name('patients.update');
    Route::post('/patients/{patient}/archive', [PatientController::class, 'archive'])->name('patients.archive');
    Route::post('/patients/{patient}/restore', [PatientController::class, 'restore'])->name('patients.restore');
});

Route::middleware(['auth', 'role:admin,receptionist,specialist'])->group(function () {
    Route::post('/reception/visits/{visit}/complete', [ReceptionDeskController::class, 'completeVisit'])->whereNumber('visit')->name('reception.visits.complete');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/staff/{directory}/{account}/edit', [StaffAccountController::class, 'edit'])
        ->whereIn('directory', ['specialists', 'clinical-staff', 'receptionists'])
        ->name('staff.edit');
    Route::put('/staff/{directory}/{account}', [StaffAccountController::class, 'update'])
        ->whereIn('directory', ['specialists', 'clinical-staff', 'receptionists'])
        ->name('staff.update');
    Route::get('/chairs/{chair}/edit', [ChairController::class, 'edit'])->name('chairs.edit');
    Route::put('/chairs/{chair}', [ChairController::class, 'update'])->name('chairs.update');
    Route::get('/services/{service}/edit', [TreatmentServiceController::class, 'edit'])->name('services.edit');
    Route::put('/services/{service}', [TreatmentServiceController::class, 'update'])->name('services.update');
});
