<?php

namespace App\Notifications;

use App\Enums\RequestType;
use App\Models\WorkflowRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly WorkflowRequest $request,
        public readonly string $event,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->request;
        $person = $request->person?->fullName() ?? 'Radnik';
        $type = $request->type->label();

        $subject = match ($this->event) {
            'waiting' => 'Zahtjev čeka odobrenje: '.$type,
            'approved' => 'Zahtjev je odobren: '.$type,
            'rejected' => 'Zahtjev je odbijen: '.$type,
            default => 'Zahtjev: '.$type,
        };

        $line = match ($this->event) {
            'waiting' => $request->type === RequestType::PersonalDataChange
                ? $person.' je prijavio promjenu osobnih podataka (nastanak '.$request->fromDate().').'
                : $person.' je podnio zahtjev ('.$request->days().' dana, '.$request->fromDate().' – '.$request->toDate().').',
            'approved' => 'Zahtjev za '.$person.' je odobren.',
            'rejected' => 'Zahtjev za '.$person.' je odbijen.',
            default => $type,
        };

        $url = $this->event === 'waiting'
            ? url('/'.$request->organization->slug.'/odobrenja/'.$request->id)
            : url('/'.$request->organization->slug.'/zahtjevi/'.$request->id);

        return (new MailMessage)
            ->subject($subject)
            ->line($line)
            ->action('Otvori zahtjev', $url);
    }
}
