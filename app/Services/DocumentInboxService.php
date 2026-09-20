<?php

namespace App\Services;

use App\Mail\ReminderMail;
use App\Models\Organization;
use App\Models\Person;
use App\Models\PersonDocument;
use Illuminate\Support\Facades\Mail;

class DocumentInboxService
{
    public function notifyNew(Organization $organization, Person $person, PersonDocument $document): void
    {
        $email = $person->email ?: $person->user?->email;
        if (! filled($email)) {
            return;
        }

        Mail::to($email)->send(new ReminderMail(
            organization: $organization,
            greetingName: $person->first_name,
            heading: 'Novi dokument u kutiji',
            intro: 'U vašu kutiju dokumenata dodan je novi akt.',
            rows: [
                ['label' => 'Dokument', 'value' => $document->label()],
            ],
            actionLabel: 'Otvori kutiju',
            actionUrl: route('organization.my-documents.index', $organization->slug),
            footer: 'Poruka je poslana iz HR evidencije.',
            subjectLine: $organization->name.': novi dokument u kutiji',
        ));
    }
}
