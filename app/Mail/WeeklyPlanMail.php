<?php

namespace App\Mail;

use App\Models\Organization;
use App\Models\Person;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyPlanMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{day: string, shift: string}>  $rows
     */
    public function __construct(
        public Organization $organization,
        public Person $person,
        public string $rangeLabel,
        public array $rows,
        public string $senderName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Plan rada '.$this->rangeLabel.' · '.$this->organization->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-plan',
        );
    }
}
