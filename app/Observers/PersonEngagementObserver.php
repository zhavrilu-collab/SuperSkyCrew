<?php

namespace App\Observers;

use App\Models\Person;
use App\Services\PersonEngagementService;

class PersonEngagementObserver
{
    public function __construct(
        private readonly PersonEngagementService $engagements,
    ) {}

    public function created(Person $person): void
    {
        $this->engagements->sync($person);
    }

    public function updated(Person $person): void
    {
        if (! $person->wasChanged(PersonEngagementService::TRACKED) && ! $person->wasChanged(['ended_at'])) {
            return;
        }

        $this->engagements->sync($person);
    }
}
