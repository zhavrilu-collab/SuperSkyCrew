<?php

namespace App\Models;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowRequest extends OrganizationModel
{
    protected $fillable = [
        'organization_id',
        'workflow_id',
        'person_id',
        'submitted_by_user_id',
        'type',
        'status',
        'current_role',
        'step_index',
        'approval_path',
        'payload',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => RequestType::class,
            'status' => RequestStatus::class,
            'approval_path' => 'array',
            'payload' => 'array',
            'decided_at' => 'datetime',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowRequestAction::class)->orderBy('id');
    }

    public function isPending(): bool
    {
        return $this->status === RequestStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === RequestStatus::Approved;
    }

    public function hasLeaveDecision(): bool
    {
        return $this->isApproved() && $this->type === RequestType::LeaveAnnual;
    }

    public function decisionNumber(): string
    {
        $year = $this->decided_at?->year ?? (int) now()->year;

        return (string) ($this->payload['decision']['number'] ?? sprintf('GO-%d/%d', $this->id, $year));
    }

    public function fromDate(): ?string
    {
        return $this->payload['from'] ?? null;
    }

    public function toDate(): ?string
    {
        return $this->payload['to'] ?? null;
    }

    public function days(): int
    {
        return (int) ($this->payload['days'] ?? 0);
    }

    public function absenceCode(): ?string
    {
        return $this->payload['absence_code'] ?? $this->type->defaultAbsenceCode();
    }

    public function minutes(): ?int
    {
        $minutes = $this->payload['minutes'] ?? null;

        return $minutes === null ? null : (int) $minutes;
    }

    public function punchId(): ?int
    {
        $id = $this->payload['punch_id'] ?? null;

        return $id === null ? null : (int) $id;
    }

    public function changeCount(): int
    {
        return count($this->payload['changes'] ?? []);
    }

    public function isLatePersonalData(): bool
    {
        return $this->type === RequestType::PersonalDataChange && (bool) ($this->payload['late'] ?? false);
    }
}
