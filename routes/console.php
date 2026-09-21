<?php

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Services\PeriodLockService;
use App\Services\ReminderService;
use App\Services\RetentionService;
use App\Services\TimeCloseService;
use App\Services\OrganizationTrialService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('hr:retention-propose', function (RetentionService $retention) {
    $count = 0;
    Organization::query()->where('status', OrganizationStatus::Active)->orderBy('id')->each(function (Organization $organization) use ($retention, &$count) {
        $count += $retention->proposeDue($organization);
    });
    $photos = $retention->purgePunchPhotos(30);
    $this->info('Predloženo za brisanje: '.$count.'; fotografije prijava: '.$photos);
})->purpose('Predloži brisanje dosjea nakon isteka zadržavanja')->daily();

Artisan::command('hr:reminders', function (ReminderService $reminders) {
    $count = $reminders->runAll();
    $this->info('Poslano podsjetnika: '.$count);
})->purpose('E-mail: istek dokumenata, zaboravljena odjava, nekompletan slog 5. i 7. dana')->daily();

Artisan::command('hr:close-time', function (TimeCloseService $closer, PeriodLockService $locks) {
    $closed = 0;
    $locked = 0;
    Organization::query()->where('status', OrganizationStatus::Active)->orderBy('id')->each(function (Organization $organization) use ($closer, $locks, &$closed, &$locked) {
        $closed += $closer->closeYesterday($organization);
        if ($locks->lockPreviousIfDue($organization) !== null) {
            $locked++;
        }
    });
    $this->info('Zatvoreno dana: '.$closed.'; zaključano razdoblja: '.$locked);
})->purpose('Zatvori jučerašnje slogove (missing out) i automatski zaključaj prethodni mjesec')->daily();

Artisan::command('hr:expire-trials', function (OrganizationTrialService $trials) {
    $count = $trials->expireAllDue();
    $this->info('Isteklo trial paketa: '.$count);
})->purpose('Nakon isteka probnog perioda spusti plan na Osnovni (bez Stripe pretplate)')->daily();
