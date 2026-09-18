<?php

use App\Http\Controllers\Auth\CoreOAuthCallbackController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OrganizationRegistrationController;
use App\Http\Controllers\Auth\StaffInviteAcceptController;
use App\Http\Controllers\OrganizationDashboardController;
use App\Http\Controllers\OrganizationPickerController;
use App\Http\Controllers\OrganizationSuspendedController;
use App\Http\Controllers\OrganizationTeamController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/prijava', [LoginController::class, 'create'])->name('login');
    Route::post('/prijava', [LoginController::class, 'store']);
    Route::get('/auth/core/callback', [CoreOAuthCallbackController::class, 'create'])->name('auth.core.callback');
    Route::get('/poziv/{token}', [StaffInviteAcceptController::class, 'show'])->name('staff-invite.show');
    Route::post('/poziv/{token}', [StaffInviteAcceptController::class, 'store'])->name('staff-invite.store');
});

Route::get('/registracija', [OrganizationRegistrationController::class, 'create'])->name('register.organization');
Route::post('/registracija', [OrganizationRegistrationController::class, 'store'])
    ->middleware('throttle:6,1');

Route::middleware('auth')->group(function () {
    Route::post('/odjava', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/registracija-ceka', [OrganizationRegistrationController::class, 'pending'])->name('registration.pending');
    Route::get('/registracija-ceka/stanje', [OrganizationRegistrationController::class, 'status'])->name('registration.pending.status');
    Route::get('/odabir-tvrtke', [OrganizationPickerController::class, 'index'])->name('organization.pick');
    Route::post('/odabir-tvrtke', [OrganizationPickerController::class, 'store'])->name('organization.pick.store');
    Route::get('/pristup-suspendiran/{slug}', [OrganizationSuspendedController::class, 'show'])->name('organization.suspended');
});

Route::prefix('{slug}')
    ->where(['slug' => '[a-z0-9\-]+'])
    ->middleware('kiosk')
    ->group(function () {
        Route::get('/kiosk/{token}', [\App\Http\Controllers\KioskController::class, 'show'])->name('organization.kiosk');
        Route::post('/kiosk/{token}/pin', [\App\Http\Controllers\KioskController::class, 'identify'])
            ->middleware('throttle:10,1')
            ->name('organization.kiosk.identify');
        Route::post('/kiosk/{token}/prijava', [\App\Http\Controllers\KioskController::class, 'punch'])->name('organization.kiosk.punch');
        Route::post('/kiosk/{token}/odjava-ekrana', [\App\Http\Controllers\KioskController::class, 'reset'])->name('organization.kiosk.reset');
    });

Route::prefix('{slug}')
    ->where(['slug' => '[a-z0-9\-]+'])
    ->middleware(['auth', 'organization'])
    ->group(function () {
        Route::get('/', OrganizationDashboardController::class)->name('organization.dashboard');
        Route::get('/prijava', [\App\Http\Controllers\ClockController::class, 'show'])->name('organization.clock');
        Route::post('/prijava', [\App\Http\Controllers\ClockController::class, 'store'])->name('organization.clock.store');
        Route::get('/struktura', [\App\Http\Controllers\StructureController::class, 'index'])->name('organization.structure.index');
        Route::post('/struktura/odjeli', [\App\Http\Controllers\StructureController::class, 'storeDepartment'])->name('organization.structure.departments.store');
        Route::put('/struktura/odjeli/{department}', [\App\Http\Controllers\StructureController::class, 'updateDepartment'])->name('organization.structure.departments.update');
        Route::delete('/struktura/odjeli/{department}', [\App\Http\Controllers\StructureController::class, 'destroyDepartment'])->name('organization.structure.departments.destroy');
        Route::post('/struktura/radna-mjesta', [\App\Http\Controllers\StructureController::class, 'storePosition'])->name('organization.structure.positions.store');
        Route::put('/struktura/radna-mjesta/{position}', [\App\Http\Controllers\StructureController::class, 'updatePosition'])->name('organization.structure.positions.update');
        Route::delete('/struktura/radna-mjesta/{position}', [\App\Http\Controllers\StructureController::class, 'destroyPosition'])->name('organization.structure.positions.destroy');
        Route::post('/struktura/mjesta-troska', [\App\Http\Controllers\StructureController::class, 'storeCostCenter'])->name('organization.structure.cost-centers.store');
        Route::put('/struktura/mjesta-troska/{costCenter}', [\App\Http\Controllers\StructureController::class, 'updateCostCenter'])->name('organization.structure.cost-centers.update');
        Route::delete('/struktura/mjesta-troska/{costCenter}', [\App\Http\Controllers\StructureController::class, 'destroyCostCenter'])->name('organization.structure.cost-centers.destroy');
        Route::get('/kadrovi', [\App\Http\Controllers\PersonController::class, 'index'])->name('organization.people.index');
        Route::get('/kadrovi/novi', [\App\Http\Controllers\PersonController::class, 'create'])->name('organization.people.create');
        Route::get('/kadrovi/maticna-knjiga', [\App\Http\Controllers\PeopleRegisterController::class, 'book'])->name('organization.people.book');
        Route::get('/kadrovi/izvoz', [\App\Http\Controllers\PeopleRegisterController::class, 'export'])->name('organization.people.export');
        Route::get('/kadrovi/fluktuacija', [\App\Http\Controllers\PeopleRegisterController::class, 'turnover'])->name('organization.people.turnover');
        Route::post('/kadrovi', [\App\Http\Controllers\PersonController::class, 'store'])->name('organization.people.store');
        Route::get('/kadrovi/{person}/pregled', [\App\Http\Controllers\PersonController::class, 'review'])->name('organization.people.review');
        Route::get('/kadrovi/{person}/uor', [\App\Http\Controllers\PersonPrintController::class, 'contract'])->name('organization.people.contract');
        Route::get('/kadrovi/{person}/uputnica', [\App\Http\Controllers\PersonPrintController::class, 'referral'])->name('organization.people.referral');
        Route::get('/kadrovi/{person}/uredi', [\App\Http\Controllers\PersonController::class, 'edit'])->name('organization.people.edit');
        Route::put('/kadrovi/{person}', [\App\Http\Controllers\PersonController::class, 'update'])->name('organization.people.update');
        Route::post('/kadrovi/{person}/kvalifikacije', [\App\Http\Controllers\QualificationController::class, 'store'])->name('organization.qualifications.store');
        Route::delete('/kadrovi/{person}/kvalifikacije/{qualification}', [\App\Http\Controllers\QualificationController::class, 'destroy'])->name('organization.qualifications.destroy');
        Route::post('/kadrovi/{person}/zaposli', [\App\Http\Controllers\PersonController::class, 'hire'])->name('organization.people.hire');
        Route::get('/isteci', [\App\Http\Controllers\ExpiryController::class, 'index'])->name('organization.expiries.index');
        Route::get('/moj-tjedan', [\App\Http\Controllers\TimesheetController::class, 'mine'])->name('organization.timesheet.mine');
        Route::get('/predaje', [\App\Http\Controllers\DocumentHandoverController::class, 'index'])->name('organization.handovers.index');
        Route::post('/predaje', [\App\Http\Controllers\DocumentHandoverController::class, 'store'])->name('organization.handovers.store');
        Route::get('/raspored', [\App\Http\Controllers\ScheduleController::class, 'index'])->name('organization.schedule.index');
        Route::post('/raspored/smjene', [\App\Http\Controllers\ScheduleController::class, 'storeShift'])->name('organization.schedule.shifts.store');
        Route::delete('/raspored/smjene/{shift}', [\App\Http\Controllers\ScheduleController::class, 'destroyShift'])->name('organization.schedule.shifts.destroy');
        Route::post('/raspored/pravila', [\App\Http\Controllers\ScheduleController::class, 'storeRule'])->name('organization.schedule.rules.store');
        Route::delete('/raspored/pravila/{rule}', [\App\Http\Controllers\ScheduleController::class, 'destroyRule'])->name('organization.schedule.rules.destroy');
        Route::get('/sihterica', [\App\Http\Controllers\TimesheetController::class, 'index'])->name('organization.timesheet.index');
        Route::get('/iznimke', [\App\Http\Controllers\ExceptionQueueController::class, 'index'])->name('organization.exceptions.index');
        Route::post('/iznimke/{entry}/rijesi', [\App\Http\Controllers\ExceptionQueueController::class, 'resolve'])->name('organization.exceptions.resolve');
        Route::get('/sihterica/inspekcija', [\App\Http\Controllers\TimesheetController::class, 'inspection'])->name('organization.timesheet.inspection');
        Route::get('/sihterica/izvoz', [\App\Http\Controllers\TimesheetController::class, 'export'])->name('organization.timesheet.export');
        Route::post('/sihterica/zakljucaj', [\App\Http\Controllers\TimesheetController::class, 'lock'])->name('organization.timesheet.lock');
        Route::get('/sihterica/{person}/{date}', [\App\Http\Controllers\TimesheetController::class, 'show'])->name('organization.timesheet.day');
        Route::post('/sihterica/{person}/rucni-unos', [\App\Http\Controllers\TimesheetController::class, 'storeManual'])->name('organization.timesheet.manual');
        Route::get('/zahtjevi', [\App\Http\Controllers\StaffRequestController::class, 'index'])->name('organization.requests.index');
        Route::get('/kalendar', [\App\Http\Controllers\AbsenceCalendarController::class, 'index'])->name('organization.absences.calendar');
        Route::get('/zahtjevi/novi', [\App\Http\Controllers\StaffRequestController::class, 'create'])->name('organization.requests.create');
        Route::post('/zahtjevi', [\App\Http\Controllers\StaffRequestController::class, 'store'])->name('organization.requests.store');
        Route::get('/zahtjevi/{zahtjev}', [\App\Http\Controllers\StaffRequestController::class, 'show'])->name('organization.requests.show');
        Route::get('/zahtjevi/{zahtjev}/rjesenje', [\App\Http\Controllers\StaffRequestController::class, 'decision'])->name('organization.requests.decision');
        Route::post('/zahtjevi/{zahtjev}/odustani', [\App\Http\Controllers\StaffRequestController::class, 'cancel'])->name('organization.requests.cancel');
        Route::get('/odobrenja', [\App\Http\Controllers\ApprovalInboxController::class, 'index'])->name('organization.approvals.index');
        Route::post('/odobrenja/{zahtjev}/odobri', [\App\Http\Controllers\ApprovalInboxController::class, 'approve'])->name('organization.approvals.approve');
        Route::post('/odobrenja/{zahtjev}/odbij', [\App\Http\Controllers\ApprovalInboxController::class, 'reject'])->name('organization.approvals.reject');
        Route::get('/tim', [OrganizationTeamController::class, 'index'])->name('organization.team.index');
        Route::post('/tim/pozivnice', [OrganizationTeamController::class, 'storeInvite'])->name('organization.team.invite');
        Route::patch('/tim/{member}/uloga', [OrganizationTeamController::class, 'updateRole'])->name('organization.team.update-role');
        Route::delete('/tim/{member}', [OrganizationTeamController::class, 'destroyMember'])->name('organization.team.destroy');
    });
