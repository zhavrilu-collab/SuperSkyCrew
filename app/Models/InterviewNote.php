<?php

namespace App\Models;

use App\Enums\InterviewOutcome;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewNote extends OrganizationModel
{
    protected $table = 'person_interview_notes';

    protected $fillable = [
        'organization_id',
        'person_id',
        'occurred_on',
        'interviewer_user_id',
        'interviewer_name',
        'outcome',
        'body',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
            'outcome' => InterviewOutcome::class,
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_user_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function interviewerLabel(): string
    {
        return $this->interviewer?->name
            ?: ($this->interviewer_name ?: '—');
    }

    public function outcomeLabel(): string
    {
        return $this->outcome instanceof InterviewOutcome
            ? $this->outcome->label()
            : (string) $this->outcome;
    }
}
