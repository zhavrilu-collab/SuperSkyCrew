<?php

use App\Http\Controllers\Auth\CoreOAuthCallbackController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OrganizationRegistrationController;
use App\Http\Controllers\Auth\StaffInviteAcceptController;
use App\Http\Controllers\OrganizationDashboardController;
use App\Http\Controllers\OrganizationLandingController;
use App\Http\Controllers\OrganizationPickerController;
use App\Http\Controllers\OrganizationSettingsController;
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
        Route::get('/', OrganizationLandingController::class)->name('organization.landing');
        Route::get('/pregled', OrganizationDashboardController::class)->name('organization.dashboard');
        Route::get('/postavke', [OrganizationSettingsController::class, 'index'])->name('organization.settings.index');
        Route::put('/postavke/organizacija', [OrganizationSettingsController::class, 'updateOrganization'])->name('organization.settings.organization');
        Route::put('/postavke/izgled', [OrganizationSettingsController::class, 'updateTheme'])->name('organization.settings.theme');
        Route::post('/postavke/logo', [OrganizationSettingsController::class, 'updateLogo'])->name('organization.settings.logo');
        Route::put('/postavke/volonteri', [OrganizationSettingsController::class, 'updateVolunteer'])->name('organization.settings.volunteer');
        Route::put('/postavke/isteci', [OrganizationSettingsController::class, 'updateExpiry'])->name('organization.settings.expiry');
        Route::post('/postavke/zadrzavanje/{document}/potvrdi', [OrganizationSettingsController::class, 'confirmRetention'])->name('organization.settings.retention.confirm');
        Route::put('/postavke/go', [OrganizationSettingsController::class, 'updateLeavePolicy'])->name('organization.settings.leave');
        Route::post('/postavke/go/pragovi', [OrganizationSettingsController::class, 'storeTenureRule'])->name('organization.settings.leave.rules.store');
        Route::delete('/postavke/go/pragovi/{rule}', [OrganizationSettingsController::class, 'destroyTenureRule'])->name('organization.settings.leave.rules.destroy');
        Route::post('/postavke/go/preracun', [OrganizationSettingsController::class, 'recalculateLeave'])->name('organization.settings.leave.recalculate');
        Route::put('/postavke/zakljucavanje', [OrganizationSettingsController::class, 'updatePeriodLock'])->name('organization.settings.period-lock');
        Route::post('/postavke/sifre', [OrganizationSettingsController::class, 'storeAbsenceCode'])->name('organization.settings.codes.store');
        Route::delete('/postavke/sifre/{code}', [OrganizationSettingsController::class, 'destroyAbsenceCode'])->name('organization.settings.codes.destroy');
        Route::post('/postavke/lokacije', [OrganizationSettingsController::class, 'storeLocation'])->name('organization.settings.locations.store');
        Route::put('/postavke/lokacije/{location}', [OrganizationSettingsController::class, 'updateLocation'])->name('organization.settings.locations.update');
        Route::get('/postavke/lokacije/{location}/kiosk', [OrganizationSettingsController::class, 'kioskPoster'])->name('organization.settings.locations.kiosk-qr');
        Route::get('/postavke/lokacije/{location}/ulaz', [OrganizationSettingsController::class, 'entrancePoster'])->name('organization.settings.locations.entrance-qr');
        Route::post('/postavke/dokumenti', [OrganizationSettingsController::class, 'storeDocumentType'])->name('organization.settings.document-types.store');
        Route::delete('/postavke/dokumenti/{documentType}', [OrganizationSettingsController::class, 'destroyDocumentType'])->name('organization.settings.document-types.destroy');
        Route::post('/postavke/slijedovi/{workflow}/koraci', [OrganizationSettingsController::class, 'storeWorkflowStep'])->name('organization.settings.workflow-steps.store');
        Route::delete('/postavke/slijedovi/{workflow}/koraci/{step}', [OrganizationSettingsController::class, 'destroyWorkflowStep'])->name('organization.settings.workflow-steps.destroy');
        Route::post('/postavke/predlosci', [OrganizationSettingsController::class, 'storeDocumentTemplate'])->name('organization.settings.templates.store');
        Route::get('/postavke/predlosci/{template}/preuzmi', [OrganizationSettingsController::class, 'downloadDocumentTemplate'])->name('organization.settings.templates.download');
        Route::delete('/postavke/predlosci/{template}', [OrganizationSettingsController::class, 'destroyDocumentTemplate'])->name('organization.settings.templates.destroy');
        Route::post('/postavke/uvoz', [OrganizationSettingsController::class, 'importPeople'])->name('organization.settings.import');
        Route::get('/postavke/uvoz/predlozak', [OrganizationSettingsController::class, 'importPeopleTemplate'])->name('organization.settings.import.template');
        Route::get('/prijava', [\App\Http\Controllers\ClockController::class, 'show'])->name('organization.clock');
        Route::get('/prijava/manifest.webmanifest', [\App\Http\Controllers\ClockController::class, 'manifest'])->name('organization.clock.manifest');
        Route::post('/prijava', [\App\Http\Controllers\ClockController::class, 'store'])->name('organization.clock.store');
        Route::post('/api/clock/punches', [\App\Http\Controllers\ClockController::class, 'store'])->name('organization.clock.api');
        Route::get('/ulaz/{token}', [\App\Http\Controllers\EntranceClockController::class, 'show'])->name('organization.entrance');
        Route::post('/ulaz/{token}', [\App\Http\Controllers\EntranceClockController::class, 'store'])->name('organization.entrance.store');
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
        Route::post('/struktura/pravne-osobe', [\App\Http\Controllers\StructureController::class, 'storeLegalEntity'])->name('organization.structure.legal-entities.store');
        Route::put('/struktura/pravne-osobe/{legalEntity}', [\App\Http\Controllers\StructureController::class, 'updateLegalEntity'])->name('organization.structure.legal-entities.update');
        Route::delete('/struktura/pravne-osobe/{legalEntity}', [\App\Http\Controllers\StructureController::class, 'destroyLegalEntity'])->name('organization.structure.legal-entities.destroy');
        Route::post('/struktura/poslovnice', [\App\Http\Controllers\StructureController::class, 'storeWorkCenter'])->name('organization.structure.work-centers.store');
        Route::put('/struktura/poslovnice/{workCenter}', [\App\Http\Controllers\StructureController::class, 'updateWorkCenter'])->name('organization.structure.work-centers.update');
        Route::delete('/struktura/poslovnice/{workCenter}', [\App\Http\Controllers\StructureController::class, 'destroyWorkCenter'])->name('organization.structure.work-centers.destroy');
        Route::post('/struktura/poslovne-jedinice', [\App\Http\Controllers\StructureController::class, 'storeEnterpriseUnit'])->name('organization.structure.enterprise-units.store');
        Route::put('/struktura/poslovne-jedinice/{enterpriseUnit}', [\App\Http\Controllers\StructureController::class, 'updateEnterpriseUnit'])->name('organization.structure.enterprise-units.update');
        Route::delete('/struktura/poslovne-jedinice/{enterpriseUnit}', [\App\Http\Controllers\StructureController::class, 'destroyEnterpriseUnit'])->name('organization.structure.enterprise-units.destroy');
        Route::get('/kadrovi', [\App\Http\Controllers\PersonController::class, 'index'])->name('organization.people.index');
        Route::get('/kadrovi/novi', [\App\Http\Controllers\PersonController::class, 'create'])->name('organization.people.create');
        Route::get('/kadrovi/maticna-knjiga', [\App\Http\Controllers\PeopleRegisterController::class, 'book'])->name('organization.people.book');
        Route::get('/kadrovi/izvoz', [\App\Http\Controllers\PeopleRegisterController::class, 'export'])->name('organization.people.export');
        Route::get('/kadrovi/fluktuacija', [\App\Http\Controllers\PeopleRegisterController::class, 'turnover'])->name('organization.people.turnover');
        Route::get('/kadrovi/place', [\App\Http\Controllers\PeopleRegisterController::class, 'payroll'])->name('organization.people.payroll');
        Route::get('/kadrovi/place/izvoz', [\App\Http\Controllers\PeopleRegisterController::class, 'payrollExport'])->name('organization.people.payroll-export');
        Route::post('/kadrovi', [\App\Http\Controllers\PersonController::class, 'store'])->name('organization.people.store');
        Route::get('/kadrovi/{person}/pregled', [\App\Http\Controllers\PersonController::class, 'review'])->name('organization.people.review');
        Route::get('/kadrovi/{person}/iskaznica', [\App\Http\Controllers\PersonPrintController::class, 'badge'])->name('organization.people.badge');
        Route::post('/kadrovi/{person}/qr', [\App\Http\Controllers\PersonController::class, 'rotateQr'])->name('organization.people.qr.rotate');
        Route::post('/kadrovi/{person}/clock-token', [\App\Http\Controllers\PersonController::class, 'rotateClockToken'])->name('organization.people.clock-token.rotate');
        Route::get('/kadrovi/{person}/clanak-10', [\App\Http\Controllers\PersonPrintController::class, 'articleTen'])->name('organization.people.article-ten');
        Route::get('/kadrovi/{person}/uor', [\App\Http\Controllers\PersonPrintController::class, 'contract'])->name('organization.people.contract');
        Route::get('/kadrovi/{person}/uputnica', [\App\Http\Controllers\PersonPrintController::class, 'referral'])->name('organization.people.referral');
        Route::get('/kadrovi/{person}/uredi', [\App\Http\Controllers\PersonController::class, 'edit'])->name('organization.people.edit');
        Route::put('/kadrovi/{person}', [\App\Http\Controllers\PersonController::class, 'update'])->name('organization.people.update');
        Route::post('/kadrovi/{person}/kvalifikacije', [\App\Http\Controllers\QualificationController::class, 'store'])->name('organization.qualifications.store');
        Route::delete('/kadrovi/{person}/kvalifikacije/{qualification}', [\App\Http\Controllers\QualificationController::class, 'destroy'])->name('organization.qualifications.destroy');
        Route::post('/kadrovi/{person}/dokumenti', [\App\Http\Controllers\PersonDocumentController::class, 'store'])->name('organization.documents.store');
        Route::get('/kadrovi/{person}/dokumenti/{document}/preuzmi', [\App\Http\Controllers\PersonDocumentController::class, 'download'])->name('organization.documents.download');
        Route::get('/kadrovi/{person}/predlosci/{template}', [\App\Http\Controllers\PersonDocumentController::class, 'fill'])->name('organization.documents.fill');
        Route::post('/kadrovi/{person}/predlosci/{template}', [\App\Http\Controllers\PersonDocumentController::class, 'fillStore'])->name('organization.documents.fill-store');
        Route::delete('/kadrovi/{person}/dokumenti/{document}', [\App\Http\Controllers\PersonDocumentController::class, 'destroy'])->name('organization.documents.destroy');
        Route::post('/kadrovi/{person}/ugovori', [\App\Http\Controllers\EmploymentContractController::class, 'store'])->name('organization.contracts.store');
        Route::delete('/kadrovi/{person}/ugovori/{employmentContract}', [\App\Http\Controllers\EmploymentContractController::class, 'destroy'])->name('organization.contracts.destroy');
        Route::post('/kadrovi/{person}/cv', [\App\Http\Controllers\SelectionController::class, 'storeCv'])->name('organization.people.cv.store');
        Route::get('/kadrovi/{person}/cv', [\App\Http\Controllers\SelectionController::class, 'downloadCv'])->name('organization.people.cv.download');
        Route::delete('/kadrovi/{person}/cv', [\App\Http\Controllers\SelectionController::class, 'destroyCv'])->name('organization.people.cv.destroy');
        Route::post('/kadrovi/{person}/razgovori', [\App\Http\Controllers\SelectionController::class, 'storeNote'])->name('organization.people.notes.store');
        Route::delete('/kadrovi/{person}/razgovori/{note}', [\App\Http\Controllers\SelectionController::class, 'destroyNote'])->name('organization.people.notes.destroy');
        Route::post('/kadrovi/{person}/zaposli', [\App\Http\Controllers\PersonController::class, 'hire'])->name('organization.people.hire');
        Route::get('/isteci', [\App\Http\Controllers\ExpiryController::class, 'index'])->name('organization.expiries.index');
        Route::get('/moj-tjedan', [\App\Http\Controllers\TimesheetController::class, 'mine'])->name('organization.timesheet.mine');
        Route::get('/predaje', [\App\Http\Controllers\DocumentHandoverController::class, 'index'])->name('organization.handovers.index');
        Route::post('/predaje', [\App\Http\Controllers\DocumentHandoverController::class, 'store'])->name('organization.handovers.store');
        Route::get('/raspored', [\App\Http\Controllers\ScheduleController::class, 'index'])->name('organization.schedule.index');
        Route::post('/raspored/posalji-plan', [\App\Http\Controllers\ScheduleController::class, 'sendPlan'])->name('organization.schedule.plan.send');
        Route::post('/raspored/prenesi-plan', [\App\Http\Controllers\ScheduleController::class, 'transferPlan'])->name('organization.schedule.plan.transfer');
        Route::post('/raspored/smjene', [\App\Http\Controllers\ScheduleController::class, 'storeShift'])->name('organization.schedule.shifts.store');
        Route::delete('/raspored/smjene/{shift}', [\App\Http\Controllers\ScheduleController::class, 'destroyShift'])->name('organization.schedule.shifts.destroy');
        Route::post('/raspored/pravila', [\App\Http\Controllers\ScheduleController::class, 'storeRule'])->name('organization.schedule.rules.store');
        Route::delete('/raspored/pravila/{rule}', [\App\Http\Controllers\ScheduleController::class, 'destroyRule'])->name('organization.schedule.rules.destroy');
        Route::post('/raspored/otvorene-smjene', [\App\Http\Controllers\ScheduleController::class, 'storeOpenShift'])->name('organization.schedule.open.store');
        Route::delete('/raspored/otvorene-smjene/{openShift}', [\App\Http\Controllers\ScheduleController::class, 'destroyOpenShift'])->name('organization.schedule.open.destroy');
        Route::post('/raspored/otvorene-smjene/{openShift}/preuzmi', [\App\Http\Controllers\ScheduleController::class, 'claimOpenShift'])->name('organization.schedule.open.claim');
        Route::get('/sihterica', [\App\Http\Controllers\TimesheetController::class, 'index'])->name('organization.timesheet.index');
        Route::get('/iznimke', [\App\Http\Controllers\ExceptionQueueController::class, 'index'])->name('organization.exceptions.index');
        Route::post('/iznimke/{entry}/rijesi', [\App\Http\Controllers\ExceptionQueueController::class, 'resolve'])->name('organization.exceptions.resolve');
        Route::get('/sihterica/inspekcija', [\App\Http\Controllers\TimesheetController::class, 'inspection'])->name('organization.timesheet.inspection');
        Route::get('/sihterica/inspekcija/izvoz', [\App\Http\Controllers\TimesheetController::class, 'exportInspection'])->name('organization.timesheet.inspection-export');
        Route::get('/sihterica/fond', [\App\Http\Controllers\TimesheetReportController::class, 'fund'])->name('organization.timesheet.fund');
        Route::get('/sihterica/fond/izvoz', [\App\Http\Controllers\TimesheetReportController::class, 'exportFund'])->name('organization.timesheet.fund-export');
        Route::get('/sihterica/sati-place', [\App\Http\Controllers\TimesheetReportController::class, 'payrollHours'])->name('organization.timesheet.payroll-hours');
        Route::get('/sihterica/sati-place/izvoz', [\App\Http\Controllers\TimesheetReportController::class, 'exportPayrollHours'])->name('organization.timesheet.payroll-hours-export');
        Route::get('/grant-sati', [\App\Http\Controllers\GrantHoursController::class, 'index'])->name('organization.grants.index');
        Route::post('/grant-sati/projekti', [\App\Http\Controllers\GrantHoursController::class, 'storeProject'])->name('organization.grants.projects.store');
        Route::post('/grant-sati', [\App\Http\Controllers\GrantHoursController::class, 'storeEntry'])->name('organization.grants.store');
        Route::delete('/grant-sati/{entry}', [\App\Http\Controllers\GrantHoursController::class, 'destroyEntry'])->name('organization.grants.destroy');
        Route::get('/api/payroll/hours', [\App\Http\Controllers\TimesheetReportController::class, 'apiPayrollHours'])->name('organization.payroll.hours.api');
        Route::get('/sihterica/izvoz', [\App\Http\Controllers\TimesheetController::class, 'export'])->name('organization.timesheet.export');
        Route::post('/sihterica/zakljucaj', [\App\Http\Controllers\TimesheetController::class, 'lock'])->name('organization.timesheet.lock');
        Route::post('/sihterica/prenesi-plan', [\App\Http\Controllers\TimesheetController::class, 'transferPlan'])->name('organization.timesheet.plan');
        Route::get('/sihterica/{person}/punch/{punch}/foto', [\App\Http\Controllers\TimesheetController::class, 'punchPhoto'])->name('organization.timesheet.photo');
        Route::get('/sihterica/{person}/{date}', [\App\Http\Controllers\TimesheetController::class, 'show'])->name('organization.timesheet.day');
        Route::post('/sihterica/{person}/{date}/evidencija', [\App\Http\Controllers\TimesheetController::class, 'storeEvidential'])->name('organization.timesheet.evidential');
        Route::post('/sihterica/{person}/{date}/slog', [\App\Http\Controllers\TimesheetController::class, 'storeSlog'])->name('organization.timesheet.slog');
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
