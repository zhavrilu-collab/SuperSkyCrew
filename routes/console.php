<?php

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Services\ReminderService;
use App\Services\RetentionService;
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
